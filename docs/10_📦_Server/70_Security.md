# Restrict access #

**Important remark:**
The appmonitor has no user login. Protect it by configuration of your webserver, i.e. use ip restriction and/ or basic authentication.

## User access to server ##

### General access to the web interface ###

Here is a snippet that forces a user login with basic auth - or access from a given
ip address without any password.

```txt
  <Location "/appmonitor/server">
    Require valid-user
    AuthType Basic
    # ...
    <RequireAny>
      Require valid-user
      Require ip 192.168.10.22
    </RequireAny>

  </Location>

  <Location "/appmonitor/api">
    Require all denied
  </Location>
```

Remove \<RequireAny\> and \</RequireAny\> to combine it and allow password based access
from the given ip address and from nowhere else.

### Sensitive data ###

These files _may_ contain sensitive data and could be interesting for hackers. Deny the web access for

- [approot]/server/config/
- [approot]/server/tmp/

In both directories is a .htaccess - if you set _AllowOverride Limit_ these .htaccess will be used. Otherwise create a directory or Location section to deny the web access.

```txt
  <Location "/appmonitor/server/config">
    Require all denied
  </Location>

  <Location "/appmonitor/server/tmp">
    Require all denied
  </Location>
```

## api access ##

The api access should be limited to the systems that need access to it.

```txt
  <Location "/appmonitor/api">
    ...
    Require ip 192.168.10.22
    ...
  </Location>
```

But shure it is better to have an additional password based access with api users.
