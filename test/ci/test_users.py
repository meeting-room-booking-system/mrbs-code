"""User administration (requires an admin user)."""
import re

from helpers import Client, find_form

def user_form(client, user_id=None):
  """Returns the form for adding a user, or for editing user_id."""
  html = client.get("edit_users.php")
  if user_id is None:
    first = find_form(html, lambda a: a.get("id") == "add_new_user")
    html = client.submit(first)
  else:
    first = find_form(html, lambda a: "edit_users" in (a.get("action") or ""), {"id": str(user_id)})
    html = client.submit(first, {"edit_button": "Edit"})
  return html, find_form(html, lambda a: a.get("id") == "form_edit_users")


class TestUsers:
  NAME = "ciuser"

  def listed(self, admin, text):
    return text in admin.get("edit_users.php")

  def user_id(self, admin, username):
    # The user list has a form per user; find the one in the row containing the name
    html = admin.get("edit_users.php")
    row = re.search(r'<tr[^>]*>(?:(?!</tr>).)*?' + re.escape(username) + r'.*?</tr>', html, re.S)
    assert row, f"{username} not listed"
    return int(re.search(r'name="id" value="(\d+)"', row.group(0)).group(1))

  def test_create_edit_delete(self, admin):
    assert not self.listed(admin, self.NAME)

    _, form = user_form(admin)
    admin.submit(form, {
      "f_name": self.NAME, "f_display_name": "CI User", "f_email": "ciuser@example.com",
      "f_level": "1", "password0": "ci-secret", "password1": "ci-secret",
      "update_button": "Save",
    })
    assert self.listed(admin, "CI User")
    new_id = self.user_id(admin, self.NAME)

    # The new user can log in with the password that was set
    newbie = Client()
    newbie.login(self.NAME, "ci-secret")
    assert "Log off" in newbie.get("index.php")

    # Edit, leaving the password alone
    _, form = user_form(admin, new_id)
    assert form["fields"]["f_display_name"] == "CI User"
    admin.submit(form, {"f_display_name": "CI User Edited", "f_level": "2", "update_button": "Save"})
    assert self.listed(admin, "CI User Edited")
    # The old password still works, and the user is now an admin
    admin_like = Client()
    admin_like.login(self.NAME, "ci-secret")
    find_form(admin_like.get("edit_users.php"), lambda a: a.get("id") == "add_new_user")

    # Delete
    _, form = user_form(admin, new_id)
    admin.submit(form, {"delete_button": "Delete this user"})
    assert not self.listed(admin, "CI User Edited")

  def test_password_mismatch_rejected(self, admin):
    _, form = user_form(admin)
    admin.submit(form, {
      "f_name": "cimismatch", "f_display_name": "CI Mismatch", "f_email": "m@example.com",
      "f_level": "1", "password0": "one", "password1": "two", "update_button": "Save",
    })
    assert not self.listed(admin, "cimismatch")

  def test_duplicate_name_rejected(self, admin):
    _, form = user_form(admin)
    admin.submit(form, {
      "f_name": "alice", "f_display_name": "Another Alice", "f_email": "other@example.com",
      "f_level": "1", "password0": "x", "password1": "x", "update_button": "Save",
    })
    assert not self.listed(admin, "Another Alice")

  def test_users_page_denied_to_non_admin(self, alice):
    html = alice.get("edit_users.php")
    assert "add_new_user" not in html
