"""Report (requires an admin user)."""

class TestReport:
  def report(self, client, **params):
    query = {"from_date": "2030-01-01", "to_date": "2030-01-31", "phase": "2"}
    query.update(params)
    response = client.http.get(client.url("report.php"), params=query)
    assert response.status_code == 200
    return response.text

  def test_report_all(self, admin):
    html = self.report(admin)
    for name in ("Team Standup", "Board Meeting", "Project Review",
                 "Training Session", "Late January Workshop"):
      assert name in html

  def test_report_room_filter(self, admin):
    html = self.report(admin, roommatch="Room B")
    assert "Board Meeting" in html
    assert "Team Standup" not in html

  def test_report_creator_filter(self, admin):
    html = self.report(admin, creatormatch="bob")
    assert "Board Meeting" in html
    assert "Training Session" in html
    assert "Team Standup" not in html

  def test_report_date_range(self, admin):
    html = self.report(admin, from_date="2030-01-15", to_date="2030-01-20")
    assert "Project Review" in html
    assert "Training Session" in html
    assert "Team Standup" not in html
    assert "Late January Workshop" not in html

  def test_csv_guards_formula_values(self, admin):
    # A booking name starting with '=' must be prefixed so that spreadsheets don't evaluate it
    text = self.report(admin, output_format="1")
    assert "'=1+1 Formula Check" in text
    assert "\"=1+1 Formula Check" not in text
    assert ",=1+1 Formula Check" not in text
