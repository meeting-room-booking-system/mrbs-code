"""Area administration (requires an admin user)."""
import re

from helpers import area_id, delete_area, delete_room, find_form, room_id

class TestAreas:
  NAME = "CI Area"

  def test_create_edit_delete(self, admin):
    assert area_id(admin, self.NAME) is None

    html = admin.get("admin.php")
    admin.submit(find_form(html, lambda a: a.get("id") == "add_area"), {"name": self.NAME})
    new_id = area_id(admin, self.NAME)
    assert new_id is not None

    html = admin.get(f"edit_area.php?area={new_id}")
    form = find_form(html, lambda a: "edit_area_handler" in (a.get("action") or ""))
    admin.submit(form, {"area_name": self.NAME + " Renamed", "area_res_mins": "15"})
    assert area_id(admin, self.NAME) is None
    assert area_id(admin, self.NAME + " Renamed") == new_id
    html = admin.get(f"edit_area.php?area={new_id}")
    assert find_form(html, lambda a: "edit_area_handler" in (a.get("action") or ""))["fields"]["area_res_mins"] == "15"

    delete_area(admin, new_id)
    assert area_id(admin, self.NAME + " Renamed") is None

  def test_duplicate_name_rejected(self, admin):
    html = admin.get("admin.php")
    admin.submit(find_form(html, lambda a: a.get("id") == "add_area"), {"name": "Annex"})
    html = admin.get("admin.php")
    assert len(re.findall(r">Annex</option>", html)) == 1

  def test_area_with_rooms_cannot_be_deleted(self, admin):
    html = admin.get("admin.php")
    admin.submit(find_form(html, lambda a: a.get("id") == "add_area"), {"name": self.NAME})
    new_area = area_id(admin, self.NAME)
    html = admin.get(f"admin.php?area={new_area}")
    admin.submit(find_form(html, lambda a: a.get("id") == "add_room"), {"name": "CI Kept Room"})
    room = room_id(admin, new_area, "CI Kept Room")

    delete_area(admin, new_area)
    assert area_id(admin, self.NAME) == new_area

    # Once the room has gone the area can be deleted
    delete_room(admin, new_area, room)
    delete_area(admin, new_area)
    assert area_id(admin, self.NAME) is None
