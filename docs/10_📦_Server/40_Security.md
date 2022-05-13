# User access #

Important remark:
The appmonitor has no user login. Protect it by configuration of your webserver, i.e. use ip restriction and/ or basic authentication.

# Sensitive data #

These files _may_ contain sensitive data and could be interesting for hackers. Deny the web access for

- [approot]/server/config/
- [approot]/server/tmp/

In both directories is a .htaccess - if you set _AllowOverride Limit_ these .htaccess will be used. Otherwise create a directory or Location section to deny the web access.
