"""Repeat bookings."""
import pytest

from helpers import ROOM_A, edit_form, find_form, room_ids

WEEKLY = "2"
MONDAY_DAY_OF_WEEK = "1"


def delete_series(client, entry_id):
  html = client.get(f"view_entry.php?id={entry_id}")
  form = find_form(html, lambda a: "del_entry" in (a.get("action") or ""), {"series": "1"})
  client.submit(form)


def delete_booking_or_series(client, entry_id):
  try:
    delete_series(client, entry_id)
  except AssertionError:
    # An occurrence that has been edited on its own is no longer part of the series
    html = client.get(f"view_entry.php?id={entry_id}")
    client.submit(find_form(html, lambda a: "del_entry" in (a.get("action") or "")))


@pytest.fixture()
def weekly_series(alice, request):
  """Creates a weekly series on Mondays 2030-03-04 to 2030-03-25 (4 bookings) in Room A."""
  name = "CI Repeat " + request.node.name
  form = edit_form(alice, f"year=2030&month=3&day=4&hour=10&minute=0&area=1&room={ROOM_A}")
  alice.submit(form, {
    "name": name,
    "rep_type": WEEKLY,
    "rep_day[]": [MONDAY_DAY_OF_WEEK],
    "rep_interval": "1",
    "rep_end_date": "2030-03-25",
    **room_ids(ROOM_A),
  })
  yield name
  # Remove whatever is left of the series
  while ids := alice.booking_ids(name):
    delete_booking_or_series(alice, ids[0])


class TestRepeatBookings:
  def test_create_weekly_series(self, alice, weekly_series):
    ids = alice.booking_ids(weekly_series)
    assert len(ids) == 4
    html = alice.get(f"index.php?view=month&year=2030&month=3&day=1&area=1&room={ROOM_A}&view_all=0")
    assert html.count(weekly_series) >= 4
    # The bookings know they belong to a series
    html = alice.get(f"view_entry.php?id={ids[1]}")
    assert weekly_series in html
    assert "Weekly" in html

  def test_edit_single_occurrence(self, alice, weekly_series):
    ids = alice.booking_ids(weekly_series)
    form = edit_form(alice, f"id={ids[1]}")
    alice.submit(form, {"name": weekly_series + " One-off", **room_ids(ROOM_A)})
    assert len(alice.booking_ids(weekly_series + " One-off")) == 1
    # The other three are unchanged
    assert len(alice.booking_ids(weekly_series)) == 4  # the search also matches the renamed one

  def test_edit_series(self, alice, weekly_series):
    ids = alice.booking_ids(weekly_series)
    form = edit_form(alice, f"id={ids[0]}&edit_series=1")
    alice.submit(form, {"name": weekly_series + " Renamed", **room_ids(ROOM_A)})
    assert len(alice.booking_ids(weekly_series + " Renamed")) == 4

  def test_delete_single_occurrence(self, alice, weekly_series):
    ids = alice.booking_ids(weekly_series)
    html = alice.get(f"view_entry.php?id={ids[2]}")
    form = find_form(html, lambda a: "del_entry" in (a.get("action") or ""), {"series": "0"})
    alice.submit(form)
    assert len(alice.booking_ids(weekly_series)) == 3

  def test_delete_series(self, alice, weekly_series):
    ids = alice.booking_ids(weekly_series)
    delete_series(alice, ids[0])
    assert alice.booking_ids(weekly_series) == []

  def test_conflict_with_series_rejected(self, alice, weekly_series):
    # A booking on the second Monday of the series conflicts with it
    form = edit_form(alice, f"year=2030&month=3&day=11&hour=10&minute=0&area=1&room={ROOM_A}")
    html = alice.submit(form, {"name": "CI Series Clash", **room_ids(ROOM_A)})
    assert "conflict" in html.lower()
    assert alice.booking_ids("CI Series Clash") == []
