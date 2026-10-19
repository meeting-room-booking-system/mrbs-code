"""Creating, editing and deleting bookings."""
from helpers import ROOM_A, edit_form, find_form, room_ids

class TestBookingLifecycle:
  NAME = "CI Created Booking"

  def test_create_edit_delete(self, alice):
    # Create, in Room A on a day with no other bookings
    form = edit_form(alice, f"year=2030&month=2&day=4&hour=10&minute=0&area=1&room={ROOM_A}")
    alice.submit(form, {"name": self.NAME, "description": "Created by CI", **room_ids(ROOM_A)})

    html = alice.get("index.php?view=day&year=2030&month=2&day=4&area=1")
    assert self.NAME in html
    entry_id = alice.booking_id(self.NAME)

    html = alice.get(f"view_entry.php?id={entry_id}")
    assert "Created by CI" in html

    # Edit
    form = edit_form(alice, f"id={entry_id}")
    alice.submit(form, {"name": self.NAME + " Edited", "description": "Edited by CI", **room_ids(ROOM_A)})
    # The booking can get a new id when it is edited, so look it up again
    entry_id = alice.booking_id(self.NAME + " Edited")
    html = alice.get(f"view_entry.php?id={entry_id}")
    assert self.NAME + " Edited" in html
    assert "Edited by CI" in html

    # Delete
    html = alice.get(f"view_entry.php?id={entry_id}")
    form = find_form(html, lambda a: "del_entry" in (a.get("action") or ""))
    alice.submit(form)
    html = alice.get("index.php?view=day&year=2030&month=2&day=4&area=1")
    assert self.NAME not in html

  def test_conflicting_booking_rejected(self, alice):
    # Team Standup (Room A, Mon 2030-01-14 09:00-10:00) already exists
    form = edit_form(alice, f"year=2030&month=1&day=14&hour=9&minute=0&area=1&room={ROOM_A}")
    html = alice.submit(form, {"name": "CI Conflicting Booking", **room_ids(ROOM_A)})
    assert "conflict" in html.lower()
    html = alice.get("search.php?search_str=CI+Conflicting+Booking")
    assert "view_entry.php?id=" not in html

  def test_booking_requires_login(self, anon):
    response = anon.http.get(anon.url("edit_entry.php?year=2030&month=2&day=4&hour=10&minute=0&area=1&room=1"))
    assert "Please login" in response.text or "logon" in response.text
