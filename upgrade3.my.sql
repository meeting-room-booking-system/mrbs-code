# Run this script to upgrade any postgresql or mysqlmrbs version prior to
# 1.2-pre3.

# Add an extra column to the mrbs_area and mrbs_room table for emails handling

ALTER TABLE mrbs_area
ADD COLUMN area_admin_email text;
ALTER TABLE mrbs_room
ADD COLUMN room_admin_email text;
