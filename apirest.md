# GLPi REST API:  Documentation

## Summary

* [Glossary](#glossary)
* [Important](#important)
* [Init session](#init-session)
* [Kill session](#kill-session)
* [Get my profiles](#get-my-profiles)
* [Get active profile](#get-active-profile)
* [Change active profile](#change-active-profile)
* [Get my entities](#get-my-entities)
* [Get active entities](#get-active-entities)
* [Change active entities](#change-active-entities)
* [Get full session](#get-full-session)
* [Get an item](#get-an-item)
* [Get all items](#get-all-items)
* [Get sub items](#get-sub-items)
* [Get multiple items](#get-multiple-items)
* [List searchOptions](#list-searchoptions)
* [Search items](#search-items)
* [Add item(s)](#add-items)
* [Update item(s)](#update-items)
* [Delete item(s)](#delete-items)
* [Errors](#errors)
* [Servers configuration](#servers-configuration)

## Glossary

Endpoint
:   Resource available though the api.
    The endpoint is the URL where your api can be accessed by a client application

Method
:   HTTP verbs to indicate the desired action to be performed on the identified resource.
    See: https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol#Request_methods

itemtype
:   A GLPI type, could be an asset, an itil or a configuration object, etc.
    This type must be a class who inherits CommonDTBM GLPI class.
    See [List itemtypes](https://forge.glpi-project.org/projects/glpi/embedded/class-CommonDBTM.html).

searchOption
:   A column identifier (integer) of an itemtype (ex: 1 -> id, 2 -> name, ...).
    See [List searchOptions](#list-searchoptions) endpoint.

JSON Payload
:   content of HTTP Request in json format (HTTP body)

Query string
:   URL parameters

User token
:   Used in login process instead of login/password couple.
    It represent the user with a string.
    You can find user token in the settings tabs of users.

Session token
:   A string describing a valid session in glpi.
    Except initSession endpoint who provide this token, all others require this string to be used.

App(lication) token
:   An optional way to filter the access to the api.
    On api call, it will try to find an api client matching your ip and the app toekn (if provided).
    You can define an api client with an app token in general configuration for each of your external applications to identify them (each api client have its own history).

## Important

* you should always precise a Content-Type header in your HTTP calls.
   Currently, the api supports:
  * application/json
  * multipart/form-data (for files upload, see [Add item(s)](#add-items) endpoint.

* GET requests must have an empty body. You must pass all parameters in URL.
  Failing to do so will trigger an HTTP 400 response.

* By default, sessions used in this API are read-only.
  Only Some methods have write access to session:
  * [initSession](#init-session)
  * [killSession](#kill-session)
  * [changeActiveEntities](#change-active-entities)
  * [changeActiveProfile](#change-active-profiles)

  You could pass an additional parameter "session_write=true" to bypass this default.
  This read-only mode allow to use this API with parallel calls.
  In write mode, sessions are locked and your client must wait the end of a call before the next one can execute.

* You can filter API access by enable the following parameters in GLPi General Configuration (API tab):
  * IPv4 range
  * IPv6 address
  * *App-Token* parameter: if not empty, you should pass this parameter in all of your api calls

* Session and App tokens could be provided in query string instead of header parameters.

## Init session

* **URL**: apirest.php/initSession/
* **Description**: Request a session token to uses other api endpoints.
* **Method**: GET
* **Parameters**: (Headers)
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
  * a couple *login* & *password*: 2 parameters to login with user authentication.
     You should pass this 2 parameters in [http basic auth](https://en.wikipedia.org/wiki/Basic_access_authentication).
     It consists in a Base64 string with login and password separated by ":"
     A valid Authorization header is:
        * "Authorization: Basic base64({login}:{password})"

    > **OR**

  * an *user_token* defined in User Preference (See 'Remote access key')
     You should pass this parameter in 'Authorization' HTTP header.
     A valid Authorization header is:
        * "Authorization: user_token q56hqkniwot8wntb3z1qarka5atf365taaa2uyjrn"

* **Returns**:
  * 200 (OK) with the *session_token* string.
  * 400 (Bad Request) with a message indicating an error in input parameter.
  * 401 (UNAUTHORIZED)

Examples usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Authorization: Basic Z2xwaTpnbHBp" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/initSession'

< 200 OK
< {
   "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62"
}

$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Authorization: user_token q56hqkniwot8wntb3z1qarka5atf365taaa2uyjrn" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/initSession'

< 200 OK
< {
   "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62"
}
```

## Kill session

* **URL**: apirest.php/killSession/
* **Description**: Destroy a session identified by a session token.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Returns**:
  * 200 (OK).
  * 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/killSession'

< 200 OK
```

## Get my profiles

* **URL**: [apirest.php/getMyProfiles/](getMyProfiles/?debug)
* **Description**: Return all the profiles associated to logged user.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Returns**:
  * 200 (OK) with an array of all profiles.
  * 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/getMyProfiles'

< 200 OK
< {
   'myprofiles': [
      {
         'id': 1
         'name': "Super-admin",
         'entities': [
            ...
         ],
         ...
      },
      ....
   ]
```

## Get active profile

* **URL**: [apirest.php/getActiveProfile/](getActiveProfile/?debug)
* **Description**: Return the current active profile.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Returns**:
  * 200 (OK) with an array representing current profile.
  * 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/getActiveProfile'

< 200 OK
< {
      'name': "Super-admin",
      'entities': [
         ...
      ]
   }
```

## Change active profile

* **URL**: [apirest.php/changeActiveProfile/](changeActiveProfile/?profiles_id=4&debug)
* **Description**: Change active profile to the profiles_id one. See [getMyProfiles](#get-my-profiles) endpoint for possible profiles.
* **Method**: POST
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (JSON Payload)
  * *profiles_id*: (default 'all') ID of the new active profile. Mandatory.
* **Returns**:
  * 200 (OK).
  * 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"profiles_id": 4}' \
'http://path/to/glpi/apirest.php/changeActiveProfile'

< 200 OK
```

## Get my entities

* **URL**: [apirest.php/getMyEntities/](getMyEntities/?debug)
* **Description**: Return all the possible entities of the current logged user (and for current active profile).
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Returns**:
  * 200 (OK) with an array of all entities (with id and name).
  * 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/getMyEntities'

< 200 OK
< {
   'myentities': [
     {
       'id':   71
       'name': "my_entity"
     },
   ....
   ]
  }
```

## Get active entities

* **URL**: [apirest.php/getActiveEntities/](getActiveEntities/?debug)
* **Description**: Return active entities of current logged user.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Returns**:
  * 200 (OK) with an array with 3 keys:
    * *active_entity*: current set entity.
    * *active_entity_recursive*: boolean, if we see sons of this entity.
    * *active_entities*: array all active entities (active_entity and its sons).
  * 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/getMyEntities'

< 200 OK
< {
   'active_entity': {
      'id': 1,
      'active_entity_recursive': true,
      'active_entities': [
        {"id":1},
        {"id":71},...
      ]
   }
}
```

## Change active entities

* **URL**: [apirest.php/changeActiveEntities/](changeActiveEntities/?entities_id=1&is_recursive=0&debug)
* **Description**: Change active entity to the entities_id one. See [getMyEntities](#get-my-entities) endpoint for possible entities.
* **Method**: POST
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (JSON Payload)
  * *entities_id*: (default 'all') ID of the new active entity ("all" => load all possible entities). Optional.
  * *is_recursive*: (default false) Also display sub entities of the active entity.  Optional.
* **Returns**:
  * 200 (OK).
  * 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"entities_id": 1, "is_recursive": true}' \
'http://path/to/glpi/apirest.php/changeActiveEntities'

< 200 OK
```

## Get full session

* **URL**: [apirest.php/getFullSession/](getFullSession/?debug)
* **Description**: Return the current php $_SESSION.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Returns**:
  * 200 (OK) with an array representing the php session.
  * 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/getFullSession'

< 200 OK
< {
      'glpi_plugins': ...,
      'glpicookietest': ...,
      'glpicsrftokens': ...,
      ...
   }
```

## Get an item

* **URL**: [apirest.php/:itemtype/:id](User/2?debug)
* **Description**: Return the instance fields of itemtype identified by id.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (query string)
  * *id*: unique identifier of the itemtype. Mandatory.
  * *expand_dropdowns* (default: false): show dropdown name instead of id. Optional.
  * *get_hateoas* (default: true): Show relations of the item in a links attribute. Optional.
  * *get_sha1* (default: false): Get a sha1 signature instead of the full answer. Optional.
  * *with_devices*: Only for [Computer, NetworkEquipment, Peripheral, Phone, Printer], retrieve the associated components. Optional.
  * *with_disks*: Only for Computer, retrieve the associated file-systems. Optional.
  * *with_softwares*: Only for Computer, retrieve the associated software's installations. Optional.
  * *with_connections*: Only for Computer, retrieve the associated direct connections (like peripherals and printers) .Optional.
  * *with_networkports*: Retrieve all network's connections and advanced network's informations. Optional.
  * *with_infocoms*: Retrieve financial and administrative informations. Optional.
  * *with_contracts*: Retrieve associated contracts. Optional.
  * *with_documents*: Retrieve associated external documents. Optional.
  * *with_tickets*: Retrieve associated itil tickets. Optional.
  * *with_problems*: Retrieve associated itil problems. Optional.
  * *with_changes*: Retrieve associated itil changes. Optional.
  * *with_notes*: Retrieve Notes. Optional.
  * *with_logs*: Retrieve historical. Optional.
* **Returns**:
  * 200 (OK) with item data (Last-Modified header should contain the date of last modification of the item).
  * 401 (UNAUTHORIZED).
  * 404 (NOT FOUND).

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/Computer/71?expand_drodpowns=true'

< 200 OK
< {
    "id": 71,
    "entities_id": "Root Entity",
    "name": "adelaunay-ThinkPad-Edge-E320",
    "serial": "12345",
    "otherserial": "test2",
    "contact": "adelaunay",
    "contact_num": null,
    "users_id_tech": " ",
    "groups_id_tech": " ",
    "comment": "test222222qsdqsd",
    "date_mod": "2015-09-25 09:33:41",
    "operatingsystems_id": "Ubuntu 15.04",
    "operatingsystemversions_id": "15.04",
    "operatingsystemservicepacks_id": " ",
    "os_license_number": null,
    "os_licenseid": null,
    "autoupdatesystems_id": " ",
    "locations_id": "00:0e:08:3b:7d:04",
    "domains_id": "",
    "networks_id": " ",
    "computermodels_id": "1298A8G",
    "computertypes_id": "Notebook",
    "is_template": 0,
    "template_name": null,
    "manufacturers_id": "LENOVO",
    "is_deleted": 0,
    "is_dynamic": 1,
    "users_id": "adelaunay",
    "groups_id": " ",
    "states_id": "Production",
    "ticket_tco": "0.0000",
    "uuid": "",
    "date_creation": null,
    "links": [{
       "rel": "Entity",
       "href": "http://path/to/glpi/api/Entity/0"
    }, {
       "rel": "OperatingSystem",
       "href": "http://path/to/glpi/api/OperatingSystem/32"
    }, {
       "rel": "OperatingSystemVersion",
       "href": "http://path/to/glpi/api/OperatingSystemVersion/48"
    }, {
       "rel": "Location",
       "href": "http://path/to/glpi/api/Location/3"
    }, {
       "rel": "Domain",
       "href": "http://path/to/glpi/api/Domain/18"
    }, {
       "rel": "ComputerModel",
       "href": "http://path/to/glpi/api/ComputerModel/11"
    }, {
       "rel": "ComputerType",
       "href": "http://path/to/glpi/api/ComputerType/3"
    }, {
       "rel": "Manufacturer",
       "href": "http://path/to/glpi/api/Manufacturer/260"
    }, {
       "rel": "User",
       "href": "http://path/to/glpi/api/User/27"
    }, {
       "rel": "State",
       "href": "http://path/to/glpi/api/State/1"
    }]
}
```

## Get all items

* **URL**: [apirest.php/:itemtype/](Computer/?debug)
* **Description**: Return a collection of rows of the itemtype.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (query string)
  * *expand_dropdowns* (default: false): show dropdown name instead of id. Optional.
  * *get_hateoas* (default: true): Show relation of item in a links attribute. Optional.
  * *only_id* (default: false): keep only id keys in returned data. Optional.
  * *range* (default: 0-50):  a string with a couple of number for start and end of pagination separated by a '-'. Ex: 150-200. Optional.
  * *sort* (default 1): id of the searchoption to sort by. Optional.
  * *order* (default ASC): ASC - Ascending sort / DESC Descending sort. Optional.
  * *searchText* (default NULL): array of filters to pass on the query (with key = field and value the text to search)
  * *is_deleted* (default: false): Return deleted element. Optional.
* **Returns**:
  * 200 (OK) with items data.
  * 206 (PARTIAL CONTENT) with items data defined by range.
  * 401 (UNAUTHORIZED).

   and theses headers:
      * *Content-Range* offset – limit / count
      * *Accept-Range* itemtype max

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/Computer/?expand_drodpowns=true'

< 200 OK
< Content-Range: 0-50/200
< Accept-Range: 990
< [
   {
      "id": 34,
      "entities_id": "Root Entity",
      "name": "glpi",
      "serial": "VMware-42 01 f4 65 27 59 a9 fb-11 bc cd b8 64 68 1f 4b",
      "otherserial": null,
      "contact": "teclib",
      "contact_num": null,
      "users_id_tech": "&nbsp;",
      "groups_id_tech": "&nbsp;",
      "comment": "x86_64/00-09-15 08:03:28",
      "date_mod": "2011-12-16 17:52:55",
      "operatingsystems_id": "Ubuntu 10.04.2 LTS",
      "operatingsystemversions_id": "2.6.32-21-server",
      "operatingsystemservicepacks_id": "&nbsp;",
      "os_license_number": null,
      "os_licenseid": null,
      "autoupdatesystems_id": "FusionInventory",
      "locations_id": "&nbsp;",
      "domains_id": "teclib.infra",
      "networks_id": "&nbsp;",
      "computermodels_id": "VMware Virtual Platform",
      "computertypes_id": "Other",
      "is_template": 0,
      "template_name": null,
      "manufacturers_id": "VMware, Inc.",
      "is_deleted": 0,
      "is_dynamic": 1,
      "users_id": "&nbsp;",
      "groups_id": "&nbsp;",
      "states_id": "Production",
      "ticket_tco": "0.0000",
      "uuid": "4201F465-2759-A9FB-11BC-CDB864681F4B",
      "links": [{
         "rel": "Entity",
         "href": "http://path/to/glpi/api/Entity/0"
      }, {
         "rel": "OperatingSystem",
         "href": "http://path/to/glpi/api/OperatingSystem/17"
      }, {
         "rel": "OperatingSystemVersion",
         "href": "http://path/to/glpi/api/OperatingSystemVersion/16"
      }, {
         "rel": "AutoUpdateSystem",
         "href": "http://path/to/glpi/api/AutoUpdateSystem/1"
      }, {
         "rel": "Domain",
         "href": "http://path/to/glpi/api/Domain/12"
      }, {
         "rel": "ComputerModel",
         "href": "http://path/to/glpi/api/ComputerModel/1"
      }, {
         "rel": "ComputerType",
         "href": "http://path/to/glpi/api/ComputerType/2"
      }, {
         "rel": "Manufacturer",
         "href": "http://path/to/glpi/api/Manufacturer/1"
      }, {
         "rel": "State",
         "href": "http://path/to/glpi/api/State/1"
      }]
   },
   {
      "id": 35,
      "entities_id": "Root Entity",
      "name": "mavm1",
      "serial": "VMware-42 20 d3 04 ac 49 ed c8-ea 15 50 49 e1 40 0f 6c",
      "otherserial": null,
      "contact": "teclib",
      "contact_num": null,
      "users_id_tech": "&nbsp;",
      "groups_id_tech": "&nbsp;",
      "comment": "x86_64/01-01-04 19:50:40",
      "date_mod": "2012-05-24 06:43:35",
      "operatingsystems_id": "Ubuntu 10.04 LTS",
      "operatingsystemversions_id": "2.6.32-21-server",
      "operatingsystemservicepacks_id": "&nbsp;",
      "os_license_num"
      ...
   }
]
```

## Get sub items

* **URL**: [apirest.php/:itemtype/:id/:sub_itemtype](User/2/Log?debug)
* **Description**: Return a collection of rows of the sub_itemtype for the identified item.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (query string)
  * id: unique identifier of the parent itemtype. Mandatory.
  * *expand_dropdowns* (default: false): show dropdown name instead of id. Optional.
  * *get_hateoas* (default: true): Show item's relations in a links attribute. Optional.
  * *only_id* (default: false): keep only id keys in returned data. Optional.
  * *range* (default: 0-50): a string with a couple of number for start and end of pagination separated by a '-' char. Ex: 150-200. Optional.
  * *sort* (default 1): id of the "searchoption" to sort by. Optional.
  * *order* (default ASC): ASC - Ascending sort / DESC Descending sort. Optional.
* **Returns**:
  * 200 (OK) with the items data.
  * 401 (UNAUTHORIZED).

   and theses headers:
      * *Content-Range* offset – limit / count
      * *Accept-Range* itemtype max

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/User/2/Log'

< 200 OK
< Content-Range: 0-50/200
< Accept-Range: 990
< [
   {
      "id": 22117,
      "itemtype": "User",
      "items_id": 2,
      "itemtype_link": "Profile",
      "linked_action": 17,
      "user_name": "glpi (27)",
      "date_mod": "2015-10-13 10:00:59",
      "id_search_option": 0,
      "old_value": "",
      "new_value": "super-admin (4)"
   }, {
      "id": 22118,
      "itemtype": "User",
      "items_id": 2,
      "itemtype_link": "",
      "linked_action": 0,
      "user_name": "glpi (2)",
      "date_mod": "2015-10-13 10:01:22",
      "id_search_option": 80,
      "old_value": "Root entity (0)",
      "new_value": "Root entity > my entity (1)"
   }, {
      ...
   }
]
```

## Get multiple items

* **URL**: apirest.php/getMultipleItems
* **Description**: Virtually call [Get an item](#get-an-item) for each line in input. So, you can have a ticket, an user in the same query.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (query string)
  * *items*: items to retrieve. Mandatory.
              Each line of this array should contains two keys:
              * itemtype
              * items_id
  * *expand_dropdowns* (default: false): show dropdown name instead of id. Optional.
  * *get_hateoas* (default: true): Show relations of the item in a links attribute. Optional.
  * *get_sha1* (default: false): Get a sha1 signature instead of the full answer. Optional.
  * *with_devices*: Only for [Computer, NetworkEquipment, Peripheral, Phone, Printer], retrieve the associated components. Optional.
  * *with_disks*: Only for Computer, retrieve the associated file-systems. Optional.
  * *with_softwares*: Only for Computer, retrieve the associated software's installations. Optional.
  * *with_connections*: Only for Computer, retrieve the associated direct connections (like peripherals and printers) .Optional.
  * *with_networkports*: Retrieve all network's connections and advanced network's informations. Optional.
  * *with_infocoms*: Retrieve financial and administrative informations. Optional.
  * *with_contracts*: Retrieve associated contracts. Optional.
  * *with_documents*: Retrieve associated external documents. Optional.
  * *with_tickets*: Retrieve associated itil tickets. Optional.
  * *with_problems*: Retrieve associated itil problems. Optional.
  * *with_changes*: Retrieve associated itil changes. Optional.
  * *with_notes*: Retrieve Notes. Optional.
  * *with_logs*: Retrieve historical. Optional.
* **Returns**:
  * 200 (OK) with item data (Last-Modified header should contain the date of last modification of the item).
  * 401 (UNAUTHORIZED).
  * 404 (NOT FOUND).

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"items": [{"itemtype": "User", "items_id": 2}, {"itemtype": "Entity", "items_id": 0}]}' \
'http://path/to/glpi/apirest.php/getMultipleItems?items\[0\]\[itemtype\]\=User&items\[0\]\[items_id\]\=2&items\[1\]\[itemtype\]\=Entity&items\[1\]\[items_id\]\=0'

< 200 OK
< Content-Range: 0-50/200
< Accept-Range: 990
< [{
   "id": 2,
   "name": "glpi",
   ...
}, {
   "id": 0,
   "name": "Root Entity",
   ...
}]
```

## List searchOptions

* **URL**: [apirest.php/listSearchOptions/:itemtype](listSearchOptions/Computer?debug)
* **Description**: List the searchoptions of provided itemtype. To use with [Search items](#search_items).
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (query string)
  * *raw*: return searchoption uncleaned (as provided by core)
* **Returns**:
  * 200 (OK) with all searchoptions of specified itemtype (format: searchoption_id: {option_content}).
  * 401 (UNAUTHORIZED).

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/listSearchOptions/Computer'
< 200 OK
< {
    "common": "Characteristics",

    1: {
      'name': 'Name'
      'table': 'glpi_computers'
      'field': 'name'
      'linkfield': 'name'
      'datatype': 'itemlink'
      'uid': 'Computer.name'
   },
   2: {
      'name': 'ID'
      'table': 'glpi_computers'
      'field': 'id'
      'linkfield': 'id'
      'datatype': 'number'
      'uid': 'Computer.id'
   },
   3: {
      'name': 'Location'
      'table': 'glpi_locations'
      'field': 'completename'
      'linkfield': 'locations_id'
      'datatype': 'dropdown'
      'uid': 'Computer.Location.completename'
   },
   ...
}
```

## Search items

* **URL**: [apirest.php/search/:itemtype/](search/Computer/?debug)
* **Description**: Expose the GLPi searchEngine and combine criteria to retrieve a list of elements of specified itemtype.
  > Note: you can use 'AllAssets' itemtype to retrieve a combination of all asset's types.
* **Method**: GET
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (query string)
  * *criteria*: array of criterion objects to filter search. Optional.
      Each criterion object must provide:
        * *link*: (optional for 1st element) logical operator in [AND, OR, AND NOT, AND NOT].
        * *field*: id of the searchoption.
        * *searchtype*: type of search in [contains¹, equals², notequals², lessthan, morethan, under, notunder].
        * *value*: the value to search.

      Ex:

         ```javascript
         ...
         "criteria":
            [
               {
                  "field":      1,
                  "searchtype": 'contains',
                  "value":      ''
               }, {
                  "link":       'AND',
                  "field":      31,
                  "searchtype": 'equals',
                  "value":      1
                }
            ]
         ...
         ```

  * *metacriteria* (optional): array of meta-criterion objects to filter search. Optional.
                                 A meta search is a link with another itemtype (ex: Computer with softwares).
      Each meta-criterion object must provide:
        * *link*: logical operator in [AND, OR, AND NOT, AND NOT]. Mandatory.
        * *itemtype*: second itemtype to link.
        * *field*: id of the searchoption.
        * *searchtype*: type of search in [contains¹, equals², notequals², lessthan, morethan, under, notunder].
        * *value*: the value to search.

      Ex:

         ```javascript
         ...
         "metacriteria":
            [
               {
                  "link":       'AND',
                  "itemtype":   'Monitor',
                  "field":      2,
                  "searchtype": 'contains',
                  "value":      ''
               }, {
                  "link":       'AND',
                  "itemtype":   'Monitor',
                  "field":      3,
                  "searchtype": 'contains',
                  "value":      ''
                }
            ]
         ...
         ```

  * *sort* (default 1): id of the searchoption to sort by. Optional.
  * *order* (default ASC): ASC - Ascending sort / DESC Descending sort. Optional.
  * *range* (default 0-50): a string with a couple of number for start and end of pagination separated by a '-'. Ex: 150-200.
                             Optional.
  * *forcedisplay*: array of columns to display (default empty = use display preferences and searched criteria).
                     Some columns will be always presents (1: id, 2: name, 80: Entity).
                     Optional.
  * *rawdata* (default false): a boolean for displaying raws data of the Search engine of glpi (like SQL request, full searchoptions, etc)
  * *withindexes* (default false): a boolean to retrieve rows indexed by items id.
   By default this option is set to false, because order of json objects (which are identified by index) cannot be garrantued  (from <http://json.org/> : An object is an unordered set of name/value pairs).
   So, we provide arrays to guarantying sorted rows.
  * *uid_cols* (default false): a boolean to identify cols by the 'uniqid' of the searchoptions instead of a numeric value (see [List searchOptions](#list-searchoptions) and 'uid' field)
  * *giveItems* (default false): a boolean to retrieve the data with the html parsed from core, new data are provided in data_html key.

  * ¹ - *contains* will use a wildcard search per default. You can restrict at the beginning using the *^* character, and/or at the end using the *$* character.
  * ² - *equals* and *notequals* are designed to be used with dropdowns. Do not expect those operators to search for a strictly equal value (see ¹ above).

* **Returns**:
  * 200 (OK) with all rows data with this format:

   ```javascript
      {
          "totalcount": ":numberofresults_without_pagination",
          "range": ":start-:end",
          "data": {
              ":items_id": {
                  ":searchoptions_id": "value",
                  ...
              },
              ":items_id": {
               ...
             }
         },
         "rawdata": {
            ...
         }
      }
   ```

  * 206 (PARTIAL CONTENT) with rows data (pagination doesn't permit to display all rows).
  * 401 (UNAUTHORIZED).

   and theses headers:
      * *Content-Range* offset – limit / count
      * *Accept-Range* itemtype max

Example usage (CURL):

```bash
curl -g -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/search/Monitor?\
criteria\[0\]\[link\]\=AND\
\&criteria\[0\]\[itemtype\]\=Monitor\
\&criteria\[0\]\[field\]\=23\
\&criteria\[0\]\[searchtype\]\=contains\
\&criteria\[0\]\[value\]\=GSM\
\&criteria\[1\]\[link\]\=AND\
\&criteria\[1\]\[itemtype\]\=Monitor\
\&criteria\[1\]\[field\]\=1\
\&criteria\[1\]\[searchtype\]\=contains\
\&criteria\[1\]\[value\]\=W2\
\&range\=0-2\&&forcedisplay\[0\]\=1'

< 200 OK
< Content-Range: 0-2/2
< Accept-Range: 990
< {"totalcount":2,"count":2,"data":{"11":{"1":"W2242","80":"Root Entity","23":"GSM"},"7":{"1":"W2252","80":"Root Entity","23":"GSM"}}}%
```

## Add item(s)

* **URL**: apirest.php/:itemtype/
* **Description**: Add an object (or multiple objects) into GLPi.
* **Method**: POST
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (JSON Payload)
  * *input*: an object with fields of itemtype to be inserted.
              You can add several items in one action by passing an array of objects.
              Mandatory.

   **Important:**
      In case of 'multipart/data' content_type (aka file upload), you should insert your parameters into
      a 'uploadManifest' parameter.
      Theses serialized data should be a json string.

* **Returns**:
  * 201 (OK) with id of added items.
  * 207 (Multi-Status) with id of added items and errors.
  * 400 (Bad Request) with a message indicating an error in input parameter.
  * 401 (UNAUTHORIZED).
  * And additional header can be provided on creation success:
    * Location when adding a single item.
    * Link on bulk addition.

Examples usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"input": {"name": "My single computer", "serial": "12345"}}' \
'http://path/to/glpi/apirest.php/Computer/'

< 201 OK
< Location: http://path/to/glpi/api/Computer/15
< {"id": 15}


$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"input": [{"name": "My first computer", "serial": "12345"}, {"name": "My 2nd computer", "serial": "67890"}, {"name": "My 3rd computer", "serial": "qsd12sd"}]}' \
'http://path/to/glpi/apirest.php/Computer/'

< 207 OK
< Link: http://path/to/glpi/api/Computer/8,http://path/to/glpi/api/Computer/9
< [ {"id":8, "message": ""}, {"id":false, "message": "You don't have permission to perform this action."}, {"id":9, "message": ""} ]
```

## Update item(s)

* **URL**: apirest.php/:itemtype/:id
* **Description**: Update an object (or multiple objects) existing in GLPi.
* **Method**: PUT
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (JSON Payload)
  * *id*: the unique identifier of the itemtype passed in URL. You **could skip** this parameter by passing it in the input payload.
  * *input*: Array of objects with fields of itemtype to be updated.
               Mandatory.
               You **could provide** in each object a key named 'id' to identify the item(s) to update.
* **Returns**:
  * 200 (OK) with update status for each item.
  * 207 (Multi-Status) with id of added items and errors.
  * 400 (Bad Request) with a message indicating an error in input parameter.
  * 401 (UNAUTHORIZED).

Example usage (CURL):

```bash
$ curl -X PUT \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"input": {"otherserial": "xcvbn"}}' \
'http://path/to/glpi/apirest.php/Computer/10'

< 200 OK
[{"10":true, "message": ""}]


$ curl -X PUT \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"input": {"id": 11,  "otherserial": "abcde"}}' \
'http://path/to/glpi/apirest.php/Computer/'

< 200 OK
[{"11":true, "message": ""}]


$ curl -X PUT \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"input": [{"id": 16,  "otherserial": "abcde"}, {"id": 17,  "otherserial": "fghij"}]}' \
'http://path/to/glpi/apirest.php/Computer/'

< 207 OK
[{"8":true, "message": ""},{"2":false, "message": "Item not found"}]
```

## Delete item(s)

* **URL**: apirest.php/:itemtype/:id
* **Description**: Delete an object existing in GLPi.
* **Method**: DELETE
* **Parameters**: (Headers)
  * *Session-Token*: session var provided by [initSession](#init-session) endpoint. Mandatory.
  * *App-Token*: authorization string provided by the GLPi api configuration. Optional.
* **Parameters**: (query string)
  * *id*: unique identifier of the itemtype passed in the URL. You **could skip** this parameter by passing it in the input payload.
      OR
  * *input* Array of id who need to be deleted. This parameter is passed by payload.

   id parameter has precedence over input payload.

  * *force_purge* (default false): boolean, if the itemtype have a dustbin, you can force purge (delete finally).
                     Optional.
  * *history* (default true): boolean, set to false to disable saving of deletion in global history.
                 Optional.
* **Returns**:
  * 200 (OK) *in case of multiple deletion*.
  * 204 (No Content) *in case of single deletion*.
  * 207 (Multi-Status) with id of deleted items and errors.
  * 400 (Bad Request) with a message indicating an error in input parameter.
  * 401 (UNAUTHORIZED).

Example usage (CURL):

```bash
$ curl -X DELETE \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
'http://path/to/glpi/apirest.php/Computer/16?force_purge=true'

< 200 OK
[{"16":true, "message": ""}]

$ curl -X DELETE \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"input": {"id": 11}, "force_purge": true}' \
'http://path/to/glpi/apirest.php/Computer/'

< 200 OK
[{"11":true, "message": ""}]


$ curl -X DELETE \
-H 'Content-Type: application/json' \
-H "Session-Token: 83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" \
-H "App-Token: f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7" \
-d '{"input": [{"id": 16}, {"id": 17}]}' \
'http://path/to/glpi/apirest.php/Computer/'

< 207 OK
[{"16":true, "message": ""},{"17":false, "message": "Item not found"}]
```

## Errors

### ERROR_ITEM_NOT_FOUND

The desired resource (itemtype-id) was not found in the GLPi database.

### ERROR_BAD_ARRAY

The HTTP body must be an an array of objects.

### ERROR_METHOD_NOT_ALLOWED

You specified an inexistent or not not allowed resource.

### ERROR_RIGHT_MISSING

The current logged user miss rights in his profile to do the provided action.
Alter this profile or choose a new one for the user in GLPi main interface.

### ERROR_SESSION_TOKEN_INVALID

The Session-Token provided in header is invalid.
You should redo an [Init session](#init-session) request.

### ERROR_SESSION_TOKEN_MISSING

You miss to provide Session-Token in header of your HTTP request.

### ERROR_APP_TOKEN_PARAMETERS_MISSING

The current API requires an App-Token header for using its methods.

### ERROR_NOT_DELETED

You must mark the item for deletion before actually deleting it

### ERROR_NOT_ALLOWED_IP

We can't find an active client defined in configuration for your IP.
Go to the GLPi Configuration > Setup menu and API tab to check IP access.

### ERROR_LOGIN_PARAMETERS_MISSING

One of theses parameter(s) is missing:

* login and password
* or user_token

### ERROR_LOGIN_WITH_CREDENTIALS_DISABLED

The GLPi setup forbid the login with credentials, you must login with your user_token instead.
See your personal preferences page or setup API access in GLPi main interface.

### ERROR_GLPI_LOGIN_USER_TOKEN

The provided user_token seems invalid.
Check your personal preferences page in GLPi main interface.

### ERROR_GLPI_LOGIN

We cannot login you into GLPi. This error is not relative to API but GLPi core.
Check the user administration and the GLPi logs files (in files/_logs directory).

### ERROR_ITEMTYPE_NOT_FOUND_NOR_COMMONDBTM

You asked a inexistent resource (endpoint). It's not a predefined (initSession, getFullSession, etc) nor a GLPi CommonDBTM resources.

See this documentation for predefined ones or [List itemtypes](https://forge.glpi-project.org/embedded/glpi/annotated.html) for available resources

### ERROR_SQL

We suspect an SQL error.
This error is not relative to API but to GLPi core.
Check the GLPi logs files (in files/_logs directory).

### ERROR_RANGE_EXCEED_TOTAL

The range parameter you provided is superior to the total count of available data.

### ERROR_GLPI_ADD

We cannot add the object to GLPi. This error is not relative to API but to GLPi core.
Check the GLPi logs files (in files/_logs directory).

### ERROR_GLPI_PARTIAL_ADD

Some of the object you wanted to add triggers an error.
Maybe a missing field or rights.
You'll find with this error a collection of results.

### ERROR_GLPI_UPDATE

We cannot update the object to GLPi. This error is not relative to API but to GLPi core.
Check the GLPi logs files (in files/_logs directory).

### ERROR_GLPI_PARTIAL_UPDATE

Some of the object you wanted to update triggers an error.
Maybe a missing field or rights.
You'll find with this error a collection of results.

### ERROR_GLPI_DELETE

We cannot delete the object to GLPi. This error is not relative to API but to GLPi core.
Check the GLPi logs files (in files/_logs directory).

### ERROR_GLPI_PARTIAL_DELETE

Some of the objects you want to delete triggers an error, maybe a missing field or rights.
You'll find with this error, a collection of results.

## Servers configuration

By default, you can use <http://path/to/glpi/apirest.php> without any additional configuration.

You'll find below some examples to configure your web server to redirect your <http://.../glpi/api/> url to the apirest.php file.

### Apache Httpd

We provide in root .htaccess of GLPi an example to enable api url rewriting.

You need to uncomment (removing #) theses lines:

```apache
#<IfModule mod_rewrite.c>
#   RewriteEngine On
#   RewriteCond %{REQUEST_FILENAME} !-f
#   RewriteCond %{REQUEST_FILENAME} !-d
#   RewriteRule api/(.*)$ apirest.php/$1
#</IfModule>
```

By enabling url rewriting, you could use api with this url : <http://path/to/glpi/api/>.
You need also to enable rewrite module in apache httpd and permit GLPi's .htaccess to override server configuration (see AllowOverride directive).

### Nginx

This example of configuration was achieved on ubuntu with php7 fpm.

```nginx
server {
   listen 80 default_server;
   listen [::]:80 default_server;

   # change here to match your GLPi directory
   root /var/www/html/glpi/;

   index index.html index.htm index.nginx-debian.html index.php;

   server_name localhost;

   location / {
      try_files $uri $uri/ =404;
      autoindex on;
   }

   location /api {
      rewrite ^/api/(.*)$ /apirest.php/$1 last;
   }

   location ~ [^/]\.php(/|$) {
      fastcgi_pass unix:/run/php/php7.0-fpm.sock;

      # regex to split $uri to $fastcgi_script_name and $fastcgi_path
      fastcgi_split_path_info ^(.+\.php)(/.+)$;

      # Check that the PHP script exists before passing it
      try_files $fastcgi_script_name =404;

      # Bypass the fact that try_files resets $fastcgi_path_info
      # # see: http://trac.nginx.org/nginx/ticket/321
      set $path_info $fastcgi_path_info;
      fastcgi_param  PATH_INFO $path_info;

      fastcgi_param  PATH_TRANSLATED    $document_root$fastcgi_script_name;
      fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;

      include fastcgi_params;

      # allow directory index
      fastcgi_index index.php;
   }
}

```
