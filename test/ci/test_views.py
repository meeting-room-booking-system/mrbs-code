"""Day, week and month grid views, and viewing a single booking."""
from helpers import MONDAY, ROOM_A, WEDNESDAY

class TestViews:
  def test_day_view(self, anon):
    html = anon.get(f"index.php?view=day&{MONDAY}&area=1")
    assert "Team Standup" in html
    assert "Board Meeting" in html
    assert "Room A" in html and "Room B" in html
    # Other days and areas are not shown
    assert "Project Review" not in html
    assert "Training Session" not in html

  def test_day_view_other_area(self, anon):
    html = anon.get("index.php?view=day&year=2030&month=1&day=18&area=2")
    assert "Training Session" in html
    assert "Room C" in html
    assert "Team Standup" not in html

  def test_week_view(self, anon):
    html = anon.get(f"index.php?view=week&{WEDNESDAY}&area=1&room={ROOM_A}&view_all=0")
    assert "Team Standup" in html
    assert "Project Review" in html
    # Different room, and a different week
    assert "Board Meeting" not in html
    assert "Late January Workshop" not in html

  def test_month_view(self, anon):
    html = anon.get(f"index.php?view=month&{MONDAY}&area=1&room={ROOM_A}&view_all=0")
    assert "Team Standup" in html
    assert "Project Review" in html
    assert "Late January Workshop" in html
    assert "Board Meeting" not in html

  def test_empty_month(self, anon):
    html = anon.get(f"index.php?view=month&year=2030&month=3&day=1&area=1&room={ROOM_A}&view_all=0")
    assert "Team Standup" not in html


class TestViewEntry:
  def test_view_entry(self, alice):
    html = alice.get("view_entry.php?id=2")
    assert "Board Meeting" in html
    assert "Quarterly planning" in html
    assert "Room B" in html
    assert "Main Building" in html

  def test_view_entry_details_of_other_user(self, alice):
    # Booking 4 belongs to bob; alice can still view it
    html = alice.get("view_entry.php?id=4")
    assert "Training Session" in html
    assert "New starter training" in html
