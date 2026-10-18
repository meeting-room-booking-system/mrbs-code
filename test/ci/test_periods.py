"""An area that uses periods rather than times."""
from helpers import PERIODS_AREA, ROOM_D, edit_form, find_form, room_ids

TUESDAY = "year=2030&month=1&day=15"


class TestPeriodsArea:
  def test_day_view_shows_periods_and_booking(self, anon):
    html = anon.get(f"index.php?view=day&{TUESDAY}&area={PERIODS_AREA}")
    for period in ("Morning", "Break", "Lunch", "Afternoon"):
      assert period in html
    assert "Period Lesson" in html
    assert "Room D" in html

  def test_view_booking(self, alice):
    html = alice.get("view_entry.php?id=6")
    assert "Period Lesson" in html
    assert "Break" in html
    assert "Periods Wing" in html

  def test_create_and_delete_booking(self, alice):
    # period=2 is "Lunch" (periods are counted in minutes after 12:00)
    form = edit_form(alice, f"year=2030&month=2&day=5&period=2&area={PERIODS_AREA}&room={ROOM_D}")
    alice.submit(form, {"name": "CI Period Booking", **room_ids(ROOM_D)})

    html = alice.get(f"index.php?view=day&year=2030&month=2&day=5&area={PERIODS_AREA}")
    assert "CI Period Booking" in html
    entry_id = alice.booking_id("CI Period Booking")
    assert "Lunch" in alice.get(f"view_entry.php?id={entry_id}")

    alice.submit(find_delete_form(alice, entry_id))
    assert alice.booking_ids("CI Period Booking") == []

  def test_conflicting_period_rejected(self, alice):
    # "Period Lesson" already has the Break period on 2030-01-15
    form = edit_form(alice, f"year=2030&month=1&day=15&period=1&area={PERIODS_AREA}&room={ROOM_D}")
    html = alice.submit(form, {"name": "CI Period Clash", **room_ids(ROOM_D)})
    assert "conflict" in html.lower()
    assert alice.booking_ids("CI Period Clash") == []


def find_delete_form(client, entry_id):
  html = client.get(f"view_entry.php?id={entry_id}")
  return find_form(html, lambda a: "del_entry" in (a.get("action") or ""))
