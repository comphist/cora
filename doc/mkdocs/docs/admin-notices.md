Sometimes, you might want to send a notification to all users, e.g. in case of
scheduled server downtime, or a software upgrade.  CorA provides a plain server
notification feature for this purpose.

- - -

To manage server notifications, change to the tab "Administration" while logged
in to CorA with an administrator account.

+ **Create a new server notification** by clicking the "Add Notification" button
  at the top of the page.  You can then specify a message type (currently, there
  is only "alert"), an expiration date, and the message itself.  Click "Preview"
  to see how the message will appear to users, or submit the message.

Setting a server notification has the following effects:

1. Users who are currently logged in to CorA will see the notification, usually
   within a minute or two.
2. All users will see the notification once after they log in, for each time
   they log in.
3. After the set expiration date, the notification will automatically be deleted
   and not shown to users anymore.

Server notification popups do not disappear automatically --- the user has to
actively acknowledge them by clicking on them.  If there are multiple server
notifications, only one will be shown at a time; the user has to close one
before the next one will be shown.

You can **delete existing notifications** in the "Server Notifications" section
of the admin page, by clicking on the ![red 'X'](img/icon-delete.png) symbol
after the notification's entry.
