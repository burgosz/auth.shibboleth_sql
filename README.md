# Shibboleth Authentication plugin for Pydio

This plugins works with the current release of Pydio, which is the 6.0.6.

This is an authentication plugin for Pydio. The plugin uses Shibboleth to login the user.
This plugin updates the existing users and creates the new one each time they login. The authentication and authorization must be done via an IdP and or an AAI.

For optimal working the Shibboleth should provide the following attributes to the Apache environment:
  - eppn: This will be the user id.
  - displayName: The full name of the user.
  - mail: Email address of the user.
  - entitlement: This controls the user rights. It must be in the format of entitlement_prefix:**entitlement**. The entitlement will be mapped to a role in Pydio. If not provided the user will only have access to its own repo.

###Installation
The installation is prety simple, just clone the repo into the plugins folder in your Pydio intallation. After cloning it delete the plugin cache of Pydio. In deault it can be found under data/cache/plugins*.ser in your Pydio installation folder.
```sh
$ git clone https://github.com/burgosz/auth.shibboleth_sql.git
```
### Setting up
Login to Pydio and in Settings menu select Application Core / Authentication. In the Main Instance section there is a drop down named Instance Type, select Shibboleth authentication with DB.

There are some additional setting:
  - Logout Url: This is the logout location of your Shibboleth.
  - Admin entitlement: Users with this entitlement will gain super admin access. Make sure that this is a valid entitlement, otherwise you can disable admin access.
  - Login Redirect: It must be: /plugins/auth.shibboleth_sql/login.php

Edit the $REDIRECTURL parameter in plugins/auth.shibboleth_sql/login.php file, to fit the address of your Pydio web page.

After this you should set up the Shibboleth, and Apache to require Shibboleth on your Pydio location.
