"""Ordinary users can only send reminders about their own bookings, and cannot request more info at all."""

BOB_BOOKING_ID = "2"  # 'Board Meeting', created by bob
ALICE_BOOKING_ID = "1"  # 'Team Standup', created by alice


def post_action(client, data):
  fields = {"series": "0", "returl": "index.php", "csrf_token": client.csrf_token(), **data}
  return client.http.post(client.url("approve_entry_handler.php"), data=fields, allow_redirects=False)


class TestApproveEntryAccess:
  def test_non_admin_cannot_request_more_info_on_others_booking(self, alice):
    response = post_action(alice, {"action": "more_info", "id": BOB_BOOKING_ID, "note": "CI"})
    assert response.status_code == 200
    assert "Location" not in response.headers

  def test_non_admin_cannot_remind_about_others_booking(self, alice):
    response = post_action(alice, {"action": "remind", "id": BOB_BOOKING_ID})
    assert response.status_code == 200
    assert "Location" not in response.headers

  def test_owner_can_remind_about_own_booking(self, alice):
    response = post_action(alice, {"action": "remind", "id": ALICE_BOOKING_ID})
    assert response.status_code == 302

  def test_admin_can_request_more_info(self, admin):
    response = post_action(admin, {"action": "more_info", "id": BOB_BOOKING_ID, "note": "CI"})
    assert response.status_code == 302
