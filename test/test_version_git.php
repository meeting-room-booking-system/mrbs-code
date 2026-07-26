<?php
declare(strict_types=1);
namespace MRBS;

use RuntimeException;

define('MRBS_ROOT', realpath(__DIR__ . '/../web'));

function function_disabled(string $name) : bool
{
  return false;
}

$git_command = 'git';

require_once MRBS_ROOT . '/version.inc';

$expected_branch = exec(
  escapeshellarg($git_command) . ' -C ' . escapeshellarg(MRBS_ROOT) .
  ' rev-parse --abbrev-ref HEAD'
);
$expected_commit = exec(
  escapeshellarg($git_command) . ' -C ' . escapeshellarg(MRBS_ROOT) .
  ' rev-parse --short HEAD'
);
$actual = get_mrbs_version();
$expected_suffix = "+git ($expected_branch $expected_commit)";

if (!str_ends_with($actual, $expected_suffix))
{
  throw new RuntimeException(
    "Expected version suffix '$expected_suffix', got '$actual'."
  );
}

echo "$actual\n";
