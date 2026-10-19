"""Search."""

class TestSearch:
  def test_search_by_name(self, alice):
    html = alice.get("search.php?search_str=Workshop")
    assert "Late January Workshop" in html
    assert "Team Standup" not in html

  def test_search_by_description(self, alice):
    html = alice.get("search.php?search_str=Quarterly")
    assert "Board Meeting" in html

  def test_search_no_results(self, alice):
    html = alice.get("search.php?search_str=nonexistentxyz")
    assert "Team Standup" not in html
    assert "Board Meeting" not in html
