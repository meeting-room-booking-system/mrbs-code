"""Room administration (requires an admin user)."""
from helpers import delete_room, edit_form, find_form, room_id, room_ids

class TestRooms:
  NAME = "CI Room"
  ANNEX = 2

  def test_create_edit_delete(self, admin):
    assert room_id(admin, self.ANNEX, self.NAME) is None

    html = admin.get(f"admin.php?area={self.ANNEX}")
    form = find_form(html, lambda a: a.get("id") == "add_room")
    admin.submit(form, {"name": self.NAME, "description": "Made by CI", "capacity": "8"})
    new_id = room_id(admin, self.ANNEX, self.NAME)
    assert new_id is not None

    html = admin.get(f"edit_room.php?room={new_id}")
    form = find_form(html, lambda a: "edit_room_handler" in (a.get("action") or ""))
    assert form["fields"]["description"] == "Made by CI"
    assert form["fields"]["capacity"] == "8"
    admin.submit(form, {"room_name": self.NAME + " Renamed", "capacity": "12"})
    assert room_id(admin, self.ANNEX, self.NAME) is None
    assert room_id(admin, self.ANNEX, self.NAME + " Renamed") == new_id
    html = admin.get(f"edit_room.php?room={new_id}")
    form = find_form(html, lambda a: "edit_room_handler" in (a.get("action") or ""))
    assert form["fields"]["capacity"] == "12"

    delete_room(admin, self.ANNEX, new_id)
    assert room_id(admin, self.ANNEX, self.NAME + " Renamed") is None

  def test_move_room_to_another_area(self, admin):
    html = admin.get(f"admin.php?area={self.ANNEX}")
    admin.submit(find_form(html, lambda a: a.get("id") == "add_room"), {"name": self.NAME})
    new_id = room_id(admin, self.ANNEX, self.NAME)
    try:
      html = admin.get(f"edit_room.php?room={new_id}")
      form = find_form(html, lambda a: "edit_room_handler" in (a.get("action") or ""))
      admin.submit(form, {"new_area": "1"})
      assert room_id(admin, 1, self.NAME) == new_id
      assert room_id(admin, self.ANNEX, self.NAME) is None
    finally:
      delete_room(admin, 1, new_id)

  def test_delete_room_removes_bookings(self, admin, alice):
    html = admin.get(f"admin.php?area={self.ANNEX}")
    admin.submit(find_form(html, lambda a: a.get("id") == "add_room"), {"name": self.NAME})
    new_id = room_id(admin, self.ANNEX, self.NAME)
    form = edit_form(alice, f"year=2030&month=4&day=2&hour=10&minute=0&area={self.ANNEX}&room={new_id}")
    alice.submit(form, {"name": "CI Room Booking", **room_ids(new_id)})
    assert alice.booking_ids("CI Room Booking")

    delete_room(admin, self.ANNEX, new_id)
    assert alice.booking_ids("CI Room Booking") == []
