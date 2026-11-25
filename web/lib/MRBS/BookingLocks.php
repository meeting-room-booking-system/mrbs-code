<?php
declare(strict_types=1);
namespace MRBS;

use Throwable;

/** Coordinate availability checks and booking changes by locking room rows. */
class BookingLocks
{
  /**
   * Transactions use READ COMMITTED so a writer waiting for a room lock sees
   * the other writer's committed bookings.
   */
  public static function beginTransaction($connection): void
  {
    if ($connection->inTransaction()) {
      throw new \RuntimeException('Begin the room transaction before running queries.');
    }
    // A fresh READ COMMITTED snapshot is essential after waiting for room locks.
    $connection->begin(true);
  }

  /**
   * For bookings moving rooms, always lock in the same order to avoid needing to retry
   * after deadlock. We use ascending room IDs.
   */
  public static function lockRooms($connection, array $room_ids): void
  {
    if (!$connection->inTransaction()) {
      throw new \RuntimeException('Room locks require an active transaction.');
    }
    $room_ids = array_values(array_unique(array_map('intval', $room_ids)));
    sort($room_ids, SORT_NUMERIC);
    foreach ($room_ids as $room_id) {
      if ($room_id < 1) {
        throw new \InvalidArgumentException('Invalid room ID.');
      }
      $row = $connection->query('SELECT id FROM ' . $connection->quote(_tbl('room'))
        . ' WHERE id=? FOR UPDATE', [$room_id])->next_row_keyed();
      if ($row === false) {
        throw new \RuntimeException("Room $room_id no longer exists.");
      }
    }
  }

  /** Run an operation inside a transaction. */
  public static function transaction($connection, callable $operation)
  {
    self::beginTransaction($connection);
    try
    {
      $result = $operation();
      $connection->commit();
      return $result;
    }
    catch (Throwable $error)
    {
      $connection->rollback();
      throw $error;
    }
  }
}
