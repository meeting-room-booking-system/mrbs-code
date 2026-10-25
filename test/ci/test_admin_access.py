"""Ordinary users cannot use the administration pages."""
from helpers import find_form, room_id

class TestAdminAccess:
  def test_area_page_denied_to_non_admin(self, alice):
    assert "edit_area_handler" not in alice.get("edit_area.php?area=1")

  def test_report_denied_to_non_admin(self, alice):
    html = alice.get("report.php?phase=2&from_date=2030-01-01&to_date=2030-01-31")
    assert "Team Standup" not in html

  def test_non_admin_cannot_edit_room(self, alice, admin):
    # Ordinary users can view room details, but not change them
    html = alice.get("edit_room.php?room=1")
    form = find_form(html, lambda a: "edit_room_handler" in (a.get("action") or ""))
    alice.submit(form, {"room_name": "CI Hijacked", "area": "1", "new_area": "1", "old_area": "1"})
    assert room_id(admin, 1, "Room A") == 1
    assert room_id(admin, 1, "CI Hijacked") is None

  def test_non_admin_cannot_delete_room(self, alice, admin):
    html = admin.get("admin.php?area=1")
    form = find_form(html, lambda a: (a.get("action") or "").startswith("del.php"), {"room": "2"})
    alice.submit({"attrs": form["attrs"], "fields": {**form["fields"], "confirm": "1",
                                                      "csrf_token": alice.csrf_token()}})
    assert room_id(admin, 1, "Room B") == 2
