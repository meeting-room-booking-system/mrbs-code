"""Password reset is shown only when $url_base is set.

The main instance sets $url_base (MRBS_URL_BASE); the nobase instance (MRBS_URL_NOBASE) does not.
"""
import pytest

from helpers import Client, NOBASE_URL


@pytest.fixture()
def nobase():
  assert NOBASE_URL, "MRBS_URL_NOBASE must be set to the instance without $url_base"
  return Client(NOBASE_URL)


class TestPasswordResetEnabled:
  def test_reset_page_shows_form(self, anon):
    html = anon.get("reset_password.php")
    assert 'name="username"' in html

  def test_lost_password_link_shown(self, anon):
    html = anon.get("admin.php")
    assert "reset_password.php" in html


class TestPasswordResetHidden:
  def test_reset_page_redirects_to_login(self, nobase):
    response = nobase.http.get(nobase.url("reset_password.php"), allow_redirects=False)
    assert response.status_code in (301, 302, 303)
    assert "index.php" in response.headers.get("Location", "")

  def test_reset_request_is_not_processed(self, nobase):
    response = nobase.http.post(nobase.url("reset_password_handler.php"),
                                data={"action": "request", "username": "alice"},
                                allow_redirects=False)
    assert response.status_code in (301, 302, 303)
    assert "index.php" in response.headers.get("Location", "")
    assert "result=request_sent" not in response.headers.get("Location", "")

  def test_lost_password_link_not_shown(self, nobase):
    # Pages that need a login show the login form to anonymous users
    html = nobase.get("admin.php")
    assert 'name="password"' in html
    assert "reset_password.php" not in html
