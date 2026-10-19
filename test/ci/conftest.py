"""Fixtures shared by the functional tests.

Environment:
  MRBS_URL   Base URL of the instance (default http://localhost:8080/)
"""
import pytest

from helpers import Client


@pytest.fixture()
def anon():
  return Client()


@pytest.fixture()
def alice():
  client = Client()
  client.login("alice")
  return client


@pytest.fixture()
def admin():
  client = Client()
  client.login("admin")
  return client
