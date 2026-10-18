"""Plumbing shared by the functional tests: an HTTP client, a form parser and common page helpers."""
import os
import re
from html.parser import HTMLParser

import requests

BASE_URL = os.environ.get("MRBS_URL", "http://localhost:8080/")
PASSWORD = "testpass"

# Fixture dates (see fixtures.my.sql)
MONDAY = "year=2030&month=1&day=14"
WEDNESDAY = "year=2030&month=1&day=16"

# Fixture area and room ids
ROOM_A = 1
PERIODS_AREA = 3
ROOM_D = 4


class FormParser(HTMLParser):
  """Collects the forms in a page, with the default values of their fields."""

  def __init__(self):
    super().__init__()
    self.forms = []
    self._form = None
    self._select = None
    self._textarea = None

  def handle_starttag(self, tag, attrs):
    a = dict(attrs)
    if tag == "form":
      self._form = {"attrs": a, "fields": {}}
      self.forms.append(self._form)
      return
    if self._form is None:
      return
    name = a.get("name")
    if tag == "input" and name and "disabled" not in a:
      type_ = (a.get("type") or "text").lower()
      if type_ in ("submit", "button", "image", "file", "reset"):
        return
      if type_ in ("checkbox", "radio") and "checked" not in a:
        return
      self._add(name, a.get("value", "on" if type_ == "checkbox" else ""))
    elif tag == "select" and name and "disabled" not in a:
      self._select = {"name": name, "first": None, "selected": None}
    elif tag == "option" and self._select is not None:
      value = a.get("value", "")
      if self._select["first"] is None:
        self._select["first"] = value
      if "selected" in a:
        self._select["selected"] = value
    elif tag == "textarea" and name and "disabled" not in a:
      self._textarea = {"name": name, "text": ""}

  def handle_endtag(self, tag):
    if tag == "form":
      self._form = None
    elif tag == "select" and self._select is not None:
      s = self._select
      value = s["selected"] if s["selected"] is not None else s["first"]
      if value is not None:
        self._add(s["name"], value)
      self._select = None
    elif tag == "textarea" and self._textarea is not None:
      self._add(self._textarea["name"], self._textarea["text"])
      self._textarea = None

  def handle_data(self, data):
    if self._textarea is not None:
      self._textarea["text"] += data

  def _add(self, name, value):
    fields = self._form["fields"]
    if name.endswith("[]"):
      fields.setdefault(name, []).append(value)
    else:
      fields[name] = value


def find_form(html, predicate, fields=None):
  """Returns the first form whose attributes satisfy predicate and whose fields include those in fields."""
  parser = FormParser()
  parser.feed(html)
  for form in parser.forms:
    if predicate(form["attrs"]) and all(form["fields"].get(k) == v for k, v in (fields or {}).items()):
      return form
  raise AssertionError("Form not found")


class Client:
  def __init__(self):
    self.http = requests.Session()

  def url(self, path):
    return BASE_URL.rstrip("/") + "/" + path.lstrip("/")

  def get(self, path):
    response = self.http.get(self.url(path))
    assert response.status_code == 200, f"{path}: HTTP {response.status_code}"
    return response.text

  def submit(self, form, overrides=None):
    data = dict(form["fields"])
    data.update(overrides or {})
    action = form["attrs"].get("action") or ""
    response = self.http.post(self.url(action), data=data)
    assert response.status_code == 200, f"{action}: HTTP {response.status_code}"
    return response.text

  def login(self, username, password=PASSWORD):
    # admin.php shows the login form when there is no logged-in user
    html = self.get("admin.php")
    form = find_form(html, lambda a: a.get("id") == "logon")
    html = self.submit(form, {"username": username, "password": password})
    assert "Please login" not in html and "Unknown user" not in html
    return html

  def csrf_token(self):
    html = self.get("index.php")
    return re.search(r'name="csrf_token" value="([0-9a-f]+)"', html).group(1)

  def booking_ids(self, name):
    """Returns the sorted ids of all the bookings matching name, found using the search page."""
    html = self.get("search.php?search_str=" + requests.utils.quote(name))
    return sorted({int(i) for i in re.findall(r"view_entry\.php\?id=(\d+)", html)})

  def booking_id(self, name):
    ids = self.booking_ids(name)
    assert ids, f"No booking found called {name!r}"
    return ids[0]


def edit_form(client, query):
  html = client.get("edit_entry.php?" + query)
  return find_form(html, lambda a: "edit_entry_handler" in (a.get("action") or ""))


def room_ids(*ids):
  # The form has a rooms[] select for every area, so the value must be set explicitly
  return {"rooms[]": [str(i) for i in ids]}


def area_id(client, name):
  html = client.get("admin.php")
  match = re.search(r'<option value="(\d+)"[^>]*>' + re.escape(name) + "</option>", html)
  return int(match.group(1)) if match else None


def room_id(client, area, name):
  html = client.get(f"admin.php?area={area}")
  match = re.search(r'edit_room\.php\?room=(\d+)">' + re.escape(name) + "</a>", html)
  return int(match.group(1)) if match else None


def delete_room(client, area, room):
  html = client.get(f"admin.php?area={area}")
  form = find_form(html, lambda a: (a.get("action") or "").startswith("del.php"), {"room": str(room)})
  client.submit(form, {"confirm": "1"})


def delete_area(client, area):
  """Attempts to delete the area; MRBS only allows this when it has no rooms."""
  html = client.get(f"admin.php?area={area}")
  form = find_form(html, lambda a: a.get("action") == "admin.php", {"area": str(area)})
  client.submit({"attrs": {"action": "del.php?type=area"}, "fields": form["fields"]})
