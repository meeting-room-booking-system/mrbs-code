"use strict";

$(function() {
  $(document).on('click', '.room-info-button', function(event) {
    event.preventDefault();
    event.stopPropagation();

    const button = $(this);
    const template = button.siblings('template.room-info-content').first();
    const content = template.html();

    if (!content || (content.trim() === ''))
    {
      return;
    }

    const roomLink = button.siblings('a').first().clone();
    roomLink.children().remove();
    const roomName = roomLink.text().trim();
    const margin = 32;
    const dialog = $('<div class="room-info-dialog"></div>')
      .html(content)
      .appendTo(document.body);

    dialog.dialog({
      modal: true,
      title: roomName,
      width: Math.min(640, Math.max(280, window.innerWidth - margin)),
      maxHeight: Math.max(240, window.innerHeight - margin),
      close: function() {
        dialog.dialog('destroy').remove();
      }
    });
  });
});
