-- Test data for the CI functional tests (MySQL).  Load after tables.my.sql.
--
-- All users have the password "testpass".
-- All bookings are in January 2030 (UTC), so the tests use fixed dates:
--   Mon 2030-01-14, Wed 2030-01-16, Fri 2030-01-18, Mon 2030-01-28

INSERT INTO mrbs_users (id, level, name, display_name, password_hash, email) VALUES
  (1, 2, 'admin', 'Admin User', '$2y$12$C4It44IjJ.jNA9lJ3RKaBOpr//xkSq9eYDQN3Cv/C.Yu1RZAdnDAe', 'admin@example.com'),
  (2, 1, 'alice', 'Alice Example', '$2y$12$C4It44IjJ.jNA9lJ3RKaBOpr//xkSq9eYDQN3Cv/C.Yu1RZAdnDAe', 'alice@example.com'),
  (3, 1, 'bob', 'Bob Example', '$2y$12$C4It44IjJ.jNA9lJ3RKaBOpr//xkSq9eYDQN3Cv/C.Yu1RZAdnDAe', 'bob@example.com');

INSERT INTO mrbs_area
  (id, area_name, sort_key, timezone, resolution, default_duration,
   morningstarts, morningstarts_minutes, eveningends, eveningends_minutes,
   private_enabled, private_default, private_mandatory, private_override,
   min_create_ahead_enabled, min_create_ahead_secs, max_create_ahead_enabled, max_create_ahead_secs,
   min_delete_ahead_enabled, min_delete_ahead_secs, max_delete_ahead_enabled, max_delete_ahead_secs,
   approval_enabled, reminders_enabled, enable_periods, confirmation_enabled, confirmed_default,
   times_along_top, default_type, periods)
VALUES
  (1, 'Main Building', 'Main Building', 'Etc/UTC', 1800, 3600, 7, 0, 18, 30,
   0, 0, 0, 'none', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 'E', '["Period 1","Period 2"]'),
  (2, 'Annex', 'Annex', 'Etc/UTC', 1800, 3600, 7, 0, 18, 30,
   0, 0, 0, 'none', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 'E', '["Period 1","Period 2"]'),
  -- Uses periods rather than times: the periods are the minutes after 12:00
  (3, 'Periods Wing', 'Periods Wing', 'Etc/UTC', 60, 60, 12, 0, 12, 3,
   0, 0, 0, 'none', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 'E', '["Morning","Break","Lunch","Afternoon"]');

INSERT INTO mrbs_room (id, area_id, room_name, sort_key, description, capacity) VALUES
  (1, 1, 'Room A', 'Room A', 'Large meeting room', 20),
  (2, 1, 'Room B', 'Room B', 'Small meeting room', 6),
  (3, 2, 'Room C', 'Room C', 'Training room', 30),
  (4, 3, 'Room D', 'Room D', 'Lesson room', 25);

INSERT INTO mrbs_entry
  (id, start_time, end_time, entry_type, room_id, create_by, modified_by, name, type, description, status, ical_uid)
VALUES
  (1, 1894611600, 1894615200, 0, 1, 'alice', 'alice', 'Team Standup',        'E', 'Daily sync',                0, 'ci-entry-1@example.com'),
  (2, 1894626000, 1894633200, 0, 2, 'bob',   'bob',   'Board Meeting',       'E', 'Quarterly planning',        0, 'ci-entry-2@example.com'),
  (3, 1894788000, 1894791600, 0, 1, 'alice', 'alice', 'Project Review',      'E', 'Review of project status',  0, 'ci-entry-3@example.com'),
  (4, 1894975200, 1894980600, 0, 3, 'bob',   'bob',   'Training Session',    'E', 'New starter training',      0, 'ci-entry-4@example.com'),
  (5, 1895828400, 1895832000, 0, 1, 'alice', 'alice', 'Late January Workshop', 'E', 'Workshop on testing',     0, 'ci-entry-5@example.com'),
  -- Tue 2030-01-15, the "Break" period
  (6, 1894708860, 1894708920, 0, 4, 'alice', 'alice', 'Period Lesson', 'E', 'Booked by period', 0, 'ci-entry-6@example.com');
