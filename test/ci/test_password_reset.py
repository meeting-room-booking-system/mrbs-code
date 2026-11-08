"""Password reset is hidden unless $url_base is set (the CI config does not set it)."""
from helpers import Client


class TestPasswordResetHidden:
  def test_reset_page_redirects_to_login(self, anon):
    response = anon.http.get(anon.url("reset_password.php"), allow_redirects=False)
    assert response.status_code in (301, 302, 303)
    assert "index.php" in response.headers.get("Location", "")

  def test_reset_request_is_not_processed(self, anon):
    response = anon.http.post(anon.url("reset_password_handler.php"),
                              data={"action": "request", "username": "alice"},
                              allow_redirects=False)
    assert response.status_code in (301, 302, 303)
    assert "index.php" in response.headers.get("Location", "")
    assert "result=request_sent" not in response.headers.get("Location", "")

  def test_lost_password_link_not_shown(self, anon):
    # Pages that need a login show the login form to anonymous users
    html = anon.get("admin.php")
    assert 'name="password"' in html
    assert "reset_password.php" not in html
