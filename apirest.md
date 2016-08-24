# GLPI REST API:  Documentation

## Summary {#summary}

* [Glossary](#glossary)
* [Important](#important)
* [Init session](#init_session)
* [Kill session](#kill_session)
* [Change active entities](#change_active_entities)
* [Get my entities](#get_my_entities)
* [Get active entities](#get_active_entities)
* [Change active profile](#change_active_profile)
* [Get my profiles](#get_my_profiles)
* [Get active profile](#get_active_profile)
* [Get full session](#get_full_session)
* [Get an item](#get_item)
* [Get all items](#get_items)
* [Get all sub items](#get_sub_items)
* [List searchOptions](#list_searchoptions)
* [Search items](#search_items)
* [Add item(s)](#add_items)
* [Update item(s)](#update_items)
* [Delete item(s)](#delete_items)
* [Errors](#errors)
* [Servers configuration](#servers_configuration)

## Glossary {#glossary}

itemtype
:   a GLPI type, could be an asset, an itil or a configuration object, etc.
    This type must be a class who inherits CommonDTBM GLPI class.
    See [List itemtypes](https://forge.glpi-project.org/projects/glpi/embedded/class-CommonDBTM.html).

searchOption
:   a column identifier (integer) of an itemtype (ex: 1 -> id, 2 -> name, ...).
    See [List searchOptions](#list_searchoptions) endpoint.

JSON Payload
:   content of HTTP Request in json format (HTTP body)

query string
:   URL parameters

Method
:   HTTP verbs to indicate the desired action to be performed on the identified resource.
    See: https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol#Request_methods


## Important {#important}

* you should always precise a Content-Type header in your HTTP calls.
   Currently, the api supports:
   - application/json
   - multipart/form-data (for files upload, see [Add item(s)](#add_items) endpoint.

* GET requests must have an empty body. You must pass all parameters in URL.
  Failing to do so will trigger an HTTP 400 response.

* By default, sessions used in this API are read-only.
  Only Some methods have write access to session:
   - [initSession](#init_session)
   - [killSession](#kill_session)
   - [changeActiveEntities](#change_active_entities)
   - [changeActiveProfile](#change_active_profiles)

  You could pass an additional parameter "session_write=true" to bypass this default.
  This read-only mode allow to use this API with parallel calls.
  In write mode, sessions are locked and your client must wait the end of a call before the next one can execute.

* You can filter API access by enable the following parameters in GLPI General Configuration (API tab):
   - IPv4 range
   - IPv6 address
   - *App-Token* parameter: if not empty, you should pass this parameter in all of your api calls


## Init session {#init_session}

* **URL**: api/initSession/
* **Description**: Request a session token to uses other api endpoints.
* **Method**: GET
* **Parameters (Headers)**
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
   - a couple *login* & *password*: 2 parameters to login with user authentication.  
     You should pass this 2 parameters in [http basic auth](https://en.wikipedia.org/wiki/Basic_access_authentication).  
     It consists in a Base64 string with login and password separated by ":"  
     A valid Authorization header is:
         - "Authorization: Basic base64({login}:{password})"

      **OR**

   - an *user_token* defined in User Preference (See 'Remote access key')  
     You should pass this parameter in 'Authorization' HTTP header.
     A valid Authorization header is:  
         - "Authorization: user_token {user_token}"

* **Returns**:
   - 200 (OK) with the *session_token* string.
   - 400 (Bad Request) with a message indicating an error in input parameter.
   - 401 (UNAUTHORIZED)

Examples usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Authorization: Basic Z2xwaTpnbHBp" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/initSession'

< 200 OK
< {
   "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62"
}

$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Authorization: user_token {mystringapikey}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/initSession'

< 200 OK
< {
   "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62"
}
```

## Kill session {#kill_session}

* **URL**: api/killSession/
* **Description**: Destroy a session identified by a session token.
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Returns**
   - 200 (OK).
   - 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/killSession'

< 200 OK
```


## Change active entities {#change_active_entities}

* **URL**: [api/changeActiveEntities/](changeActiveEntities/?entities_id=1&is_recursive=0&debug)
* **Description**: Change active entity to the entities_id one. See [getMyEntities](#get_my_entities) endpoint for possible entities.
* **Method**: POST
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Parameters (JSON Payload)**
   - *entities_id*: (default 'all') ID of the new active entity ("all" => load all possible entities). Optional.
   - *is_recursive*: (default false) Also display sub entities of the active entity.  Optional.
* **Returns**
   - 200 (OK).
   - 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
-d '{"entities_id": 1, "is_recursive": true}' \
'http://path/to/glpi/api/changeActiveEntities'

< 200 OK
```


## Get my entities {#get_my_entities}

* **URL**: [api/getMyEntities/](getMyEntities/?debug)
* **Description**: return all the possible entities of the current logged user (and for current active profile).
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Returns**
   - 200 (OK) with an array of all entities (with id and name).
   - 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/getMyEntities'

< 200 OK
< [ 71:
   {
      'id':   71
      'name': "my_entity"
   },
   ....
]
```


## Get active entities {#get_active_entities}

* **URL**: [api/getActiveEntities/](getActiveEntities/?debug)
* **Description**: return active entities of current logged user
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Returns**
   - 200 (OK) with an array with 3 keys:
      - *active_entity*: current set entity.
      - *active_entity_recursive*: boolean, if we see sons of this entity.
      - *active_entities*: array all active entities (active_entity and its sons).
   - 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/getMyEntities'

< 200 OK
< {
   'active_entity':           1
   'active_entity_recursive': true,
   'active_entities':         [
      {'1':1},
      {'71':71},
      ...
   ]
}
```


## Change active profile {#change_active_profile}

* **URL**: [api/changeActiveProfile/](changeActiveProfile/?profiles_id=4&debug)
* **Description**: Change active profile to the profiles_id one. See [getMyProfiles](#get_my_profiless) endpoint for possible profiles.
* **Method**: POST
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Parameters (JSON Payload)**
   - *profiles_id*: (default 'all') ID of the new active profile. Mandatory.
* **Returns**
   - 200 (OK).
   - 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
-d '{"profiles_id": 4}' \
'http://path/to/glpi/api/changeActiveProfile'

< 200 OK
```


## Get my profiles {#get_my_profiles}

* **URL**: [api/getMyProfiles/](getMyProfiles/?debug)
* **Description**: Return all the profiles associated to logged user.
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Returns**
   - 200 (OK) with an array of all profiles.
   - 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/getMyProfiles'

< 200 OK
< [ 4:
   {
      'name': "Super-admin",
      'entities': {
         ...
      },
      ...
   },
   ....
]
```


## Get active profile {#get_active_profile}

* **URL**: [api/getActiveProfile/](getActiveProfile/?debug)
* **Description**: return the current active profile.
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Returns**
   - 200 (OK) with an array representing current profile.
   - 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/getActiveProfile'

< 200 OK
< {
      'name': "Super-admin",
      'entities': {
         ...
      },
      ...
   }
```


## Get full session {#get_full_session}

* **URL**: [api/getFullSession/](getFullSession/?debug)
* **Description**: return the current php $_SESSION
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Returns**
   - 200 (OK) with an array representing the php session.
   - 400 (Bad Request) with a message indicating an error in input parameter.

Example usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/getFullSession'

< 200 OK
< {
      'glpi_plugins': ...,
      'glpicookietest': ...,
      'glpicsrftokens': ...,
      ...
   }
```


## Get an item {#get_item}

* **URL**: [api/:itemtype/:id](User/2?debug)
* **Description**: Return the instance fields of itemtype identified by id
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Parameters (query string)**
   - *id*: unique identifier of the itemtype. Mandatory.
   - *expand_dropdowns* (default: false): show dropdown name instead of id. Optional.
   - *get_hateoas* (default: true): Show relations of the item in a links attribute. Optional.
   - *with_components*: Only for [Computer, NetworkEquipment, Peripheral, Phone, Printer], retrieve the associated components. Optional.
   - *with_disks*: Only for Computer, retrieve the associated file-systems. Optional.
   - *with_softwares*: Only for Computer, retrieve the associated software's installations. Optional.
   - *with_connections*: Only for Computer, retrieve the associated direct connections (like peripherals and printers) .Optional.
   - *with_networkports*: Retrieve all network's connections and advanced network's informations. Optional.
   - *with_infocoms*: Retrieve financial and administrative informations. Optional.
   - *with_contracts*: Retrieve associated contracts. Optional.
   - *with_documents*: Retrieve associated external documents. Optional.
   - *with_tickets*: Retrieve associated itil tickets. Optional.
   - *with_problems*: Retrieve associated itil problems. Optional.
   - *with_changes*: Retrieve associated itil changes. Optional.
   - *with_notes*: Retrieve Notes. Optional.
   - *with_logs*: Retrieve historical. Optional.
* **Returns**
   - 200 (OK) with item data (Last-Modified header should contain the date of last modification of the item).
   - 401 (UNAUTHORIZED).
   - 404 (NOT FOUND).

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/Computer/71?expand_drodpowns=true'

< 200 OK
< {
    "id": "71",
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
    "is_template": "0",
    "template_name": null,
    "manufacturers_id": "LENOVO",
    "is_deleted": "0",
    "is_dynamic": "1",
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



## Get all items {#get_items}

* **URL**: [api/:itemtype/](Computer/?debug)
* **Description**: Return a collection of rows of the itemtype
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Parameters (query string)**
   - *expand_dropdowns* (default: false): show dropdown name instead of id. Optional.
   - *get_hateoas* (default: true): Show relation of item in a links attribute. Optional.
   - *only_id* (default: false): keep only id keys in returned data. Optional.
   - *range* (default: 0-50):  a string with a couple of number for start and end of pagination separated by a '-'. Ex: 150-200. Optional.
   - *sort* (default 1): id of the searchoption to sort by. Optional.
   - *order* (default ASC): ASC - Ascending sort / DESC Descending sort. Optional.
* **Returns**
   - 200 (OK) with items data.
   - 401 (UNAUTHORIZED).

   and theses headers:
      * *Content-Range* offset – limit / count
      * *Accept-Range* itemtype max

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/Computer/?expand_drodpowns=true'

< 200 OK
< Content-Range: 0-50/200
< Accept-Range: 990
< [
   {
      "id": "34",
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
      "is_template": "0",
      "template_name": null,
      "manufacturers_id": "VMware, Inc.",
      "is_deleted": "0",
      "is_dynamic": "1",
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
      "id": "35",
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

## Get sub items {#get_sub_items}

* **URL**: [api/:itemtype/:id/:sub_itemtype](User/2/Log?debug)
* **Description**: Return a collection of rows of the sub_itemtype for the identified item
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Parameters (query string)**
   - id: unique identifier of the parent itemtype. Mandatory.
   - *expand_dropdowns* (default: false): show dropdown name instead of id. Optional.
   - *get_hateoas* (default: true): Show item's relations in a links attribute. Optional.
   - *only_id* (default: false): keep only id keys in returned data. Optional.
   - *range* (default: 0-50): a string with a couple of number for start and end of pagination separated by a '-' char. Ex: 150-200. Optional.
   - *sort* (default 1): id of the "searchoption" to sort by. Optional.
   - *order* (default ASC): ASC - Ascending sort / DESC Descending sort. Optional.
* **Returns**
   - 200 (OK) with the items data.
   - 401 (UNAUTHORIZED).

   and theses headers:
      * *Content-Range* offset – limit / count
      * *Accept-Range* itemtype max

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/User/2/Log'

< 200 OK
< Content-Range: 0-50/200
< Accept-Range: 990
< [
   {
      "id": "22117",
      "itemtype": "User",
      "items_id": "2",
      "itemtype_link": "Profile",
      "linked_action": "17",
      "user_name": "glpi (27)",
      "date_mod": "2015-10-13 10:00:59",
      "id_search_option": "0",
      "old_value": "",
      "new_value": "super-admin (4)"
   }, {
      "id": "22118",
      "itemtype": "User",
      "items_id": "2",
      "itemtype_link": "",
      "linked_action": "0",
      "user_name": "glpi (2)",
      "date_mod": "2015-10-13 10:01:22",
      "id_search_option": "80",
      "old_value": "Root entity (0)",
      "new_value": "Root entity > my entity (1)"
   }, {
      ...
   }
]
```


## List searchOptions {#list_searchoptions}

* **URL**: [api/listSearchOptions/:itemtype](listSearchOptions/Computer?debug)
* **Description**: List the searchoptions of provided itemtype. To use with [Search items](#search_items)
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Parameters (query string)**
   - *raw*: return searchoption uncleaned (as provided by core)
* **Returns**
   - 200 (OK) with all searchoptions of specified itemtype (format: searchoption_id: {option_content}).
   - 401 (UNAUTHORIZED).

Example usage (CURL):

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/listSearchOptions/Computer'
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



## Search items {#search_items}

* **URL**: [api/search/:itemtype/](search/Computer/?debug)
* **Description**: Expose the GLPI searchEngine and combine criteria to retrieve a list of elements of specified itemtype.  
Note: you can use 'AllAssets' itemtype to retrieve a combination of all asset's types.
* **Method**: GET
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Parameters (query string)**
   - *criteria*: array of criterion objects to filter search. Optional.  
      Each criterion object must provide:
         - *link*: (optional for 1st element) logical operator in [AND, OR, AND NOT, AND NOT].
         - *field*: id of the searchoption.
         - *searchtype*: type of search in [contains, equals, notequals, lessthan, morethan, under, notunder].
         - *value*: the value to search.

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
                  "value":      '1'
                }
            ]
         ...
         ```

   - *metacriteria* (optional): array of meta-criterion objects to filter search. Optional.  
                                 A meta search is a link with another itemtype (ex: Computer with softwares).  
      Each meta-criterion object must provide:
         - *link*: logical operator in [AND, OR, AND NOT, AND NOT]. Mandatory.
         - *itemtype*: second itemtype to link.
         - *field*: id of the searchoption.
         - *searchtype*: type of search in [contains, equals, notequals, lessthan, morethan, under, notunder].
         - *value*: the value to search.

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

   - *sort* (default 1): id of the searchoption to sort by. Optional.
   - *order* (default ASC): ASC - Ascending sort / DESC Descending sort. Optional.
   - *range* (default 0-50): a string with a couple of number for start and end of pagination separated by a '-'. Ex: 150-200.
                             Optional.
   - *forcedisplay*: array of columns to display (default empty = use display preferences and searched criteria).
                     Some columns will be always presents (1: id, 2: name, 80: Entity).
                     Optional.
   - *rawdata* (default false): a boolean for displaying raws data of the Search engine of glpi (like SQL request, full searchoptions, etc)
   - *withindexes* (default false): a boolean to retrieve rows indexed by items id.  
   By default this option is set to false, because order of json objects (which are identified by index) cannot be garrantued  (from http://json.org/ : An object is an unordered set of name/value pairs).  
   So, we provide arrays to guarantying sorted rows.
   - *uid_cols* (default false): a boolean to identify cols by the 'uniqid' of the searchoptions instead of a numeric value (see [List searchOptions](#list_searchoptions) and 'uid' field)
   - *giveItems* (default false): a boolean to retrieve the data with the html parsed from core, new data are provided in data_html key.
* **Returns**
   - 200 (OK) with all rows data with this format:

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

   - 206 (PARTIAL CONTENT) with rows data (pagination doesn't permit to display all rows).
   - 401 (UNAUTHORIZED).

   and theses headers:
      * *Content-Range* offset – limit / count
      * *Accept-Range* itemtype max

Example usage (CURL):

```bash
curl -g -X GET \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/search/Monitor'\
\&criteria\[0\]\[link\]\=AND\
\&criteria\[0\]\[itemtype\]\=Monitor\
\&criteria\[0\]\[field\]\=23\
\&criteria\[0\]\[searchtype\]\=contains\
\&criteria\[0\]\[value\]\=GSM\
\&criteria\[1\]\[link\]\=AND\
\&criteria\[1\]\[itemtype\]\=Monitor\
\&criteria\[1\]\[field\]\=1\
\&criteria\[1\]\[searchtype\]\=contains\
\&criteria\[1\]\[value\]\=W2\
\&range\=0-2\&&forcedisplay\[0\]\=1

< 200 OK
< Content-Range: 0-2/2
< Accept-Range: 990
< {"totalcount":2,"count":2,"data":{"11":{"1":"W2242","80":"Root Entity","23":"GSM"},"7":{"1":"W2252","80":"Root Entity","23":"GSM"}}}%
```


## Add item(s) {#add_items}

* **URL**: api/:itemtype/
* **Description**: Add an object (or multiple objects) into GLPI.
* **Method**: POST
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Parameters (JSON Payload)**
   - *input*: an object with fields of itemtype to be inserted.  
              You can add several items in one action by passing an array of objects.
              Mandatory.

   **Important:**
      In case of 'multipart/data' content_type (aka file upload), you should insert your parameters into
      a 'uploadManifest' parameter.  
      Theses serialized data should be a json string.

* **Returns**
   - 201 (OK) with id of added items.
   - 207 (Multi-Status) with id of added items and errors.
   - 400 (Bad Request) with a message indicating an error in input parameter.
   - 401 (UNAUTHORIZED).
   - And additional header can be provided on creation success:
      - Location when adding a single item.
      - Link on bulk addition.

Examples usage (CURL):

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
-d '{"input": {"name": "My single computer", "serial": "12345"}}' \
'http://path/to/glpi/api/Computer/'

< 201 OK
< Location: http://path/to/glpi/api/Computer/15
< {"id": 15}


$ curl -X POST \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
-d '{"input": [{"name": "My first computer", "serial": "12345"}, {"name": "My 2nd computer", "serial": "67890"}, {"name": "My 3rd computer", "serial": "qsd12sd"}]}' \
'http://path/to/glpi/api/Computer/'

< 201 OK
< Link: http://path/to/glpi/api/Computer/8,http://path/to/glpi/api/Computer/9
< [ {"id":"8"}, {"id":false}, {"id":"9"} ]
```



## Update item(s) {#update_items}

* **URL**: api/:itemtype/(:id)
* **Description**: update an object (or multiple objects) existing in GLPI.
* **Method**: PUT
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Parameters (JSON Payload)**
   - *id*: the unique identifier of the itemtype passed in URL. You **could skip** this parameter by passing it in the input payload.
   - *input*: Array of objects with fields of itemtype to be updated.
               Mandatory.
               You **could provide** in each object a key named 'id' to identify the item(s) to update.
* **Returns**
   - 200 (OK) with update status for each item.
   - 207 (Multi-Status) with id of added items and errors.
   - 400 (Bad Request) with a message indicating an error in input parameter.
   - 401 (UNAUTHORIZED).

Example usage (CURL):

```bash
$ curl -X PUT \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
-d '{"input": {"otherserial": "xcvbn"}}' \
'http://path/to/glpi/api/Computer/10'

< 200 OK
[{"10":"true"}]


$ curl -X PUT \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
-d '{"input": {"id": 11,  "otherserial": "abcde"}}' \
'http://path/to/glpi/api/Computer/'

< 200 OK
[{"11":"true"}]


$ curl -X PUT \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
-d '{"input": [{"id": 16,  "otherserial": "abcde"}, {"id": 17,  "otherserial": "fghij"}]}' \
'http://path/to/glpi/api/Computer/'

< 200 OK
[{"8":"true"},{"2":"true"}]
```



## Delete item(s) {#delete_items}

* **URL**: api/:itemtype/(:id)
* **Description**: delete an object existing in GLPI
* **Method**: DELETE
* **Parameters (Headers)**
   - *Session-Token*: session var provided by [initSession](#init_session) endpoint. Mandatory.
   - *App-token*: authorization string provided by the GLPI api configuration. Optional.
* **Parameters (query string)**
   - *id*: unique identifier of the itemtype passed in the URL. You **could skip** this parameter by passing it in the input payload.
      OR
   - *input* Array of id who need to be deleted. This parameter is passed by payload.

   id parameter has precedence over input payload.

   - *force_purge* (default false): boolean, if the itemtype have a dustbin, you can force purge (delete finally).
                     Optional.
   - *history* (default true): boolean, set to false to disable saving of deletion in global history.
                 Optional.
* **Returns**
   - 200 (OK) *in case of multiple deletion*.
   - 204 (No Content) *in case of single deletion*.
   - 207 (Multi-Status) with id of deleted items and errors.
   - 400 (Bad Request) with a message indicating an error in input parameter.
   - 401 (UNAUTHORIZED).

Example usage (CURL):

```bash
$ curl -X DELETE \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
'http://path/to/glpi/api/Computer/16?force_purge=true'

< 204 OK


$ curl -X DELETE \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
-d '{"input": {"id": 11}, "force_purge": true}' \
'http://path/to/glpi/api/Computer/'

< 200 OK
[{"11":"true"}]


$ curl -X DELETE \
-H 'Content-Type: application/json' \
-H "Session-Token {83af7e620c83a50a18d3eac2f6ed05a3ca0bea62}" \
-H "App-Token {f7g3csp8mgatg5ebc5elnazakw20i9fyev1qopya7}" \
-d '{"input": [{"id": 16}, {"id": 17}]}' \
'http://path/to/glpi/api/Computer/'

< 207 OK
[{"16":"true"},{"17":"false"}]
```



## Errors {#errors}


### ERROR_ITEM_NOT_FOUND {#ERROR_ITEM_NOT_FOUND}

The desired resource (itemtype-id) was not found in the GLPI database.


### ERROR_BAD_ARRAY {#ERROR_BAD_ARRAY}

The HTTP body must be an an array of objects.


### ERROR_METHOD_NOT_ALLOWED {#ERROR_METHOD_NOT_ALLOWED}

You specified an inexistent or not not allowed resource.


### ERROR_RIGHT_MISSING {#ERROR_RIGHT_MISSING}

The current logged user miss rights in his profile to do the provided action.  
Alter this profile or choose a new one for the user in GLPI main interface.


### ERROR_SESSION_TOKEN_INVALID {#ERROR_SESSION_TOKEN_INVALID}

The Session-Token provided in header is invalid.  
You should redo an [Init session](#init_session) request.


### ERROR_SESSION_TOKEN_MISSING {#ERROR_SESSION_TOKEN_MISSING}

You miss to provide Session-Token in header of your HTTP request.


### ERROR_APP_TOKEN_PARAMETERS_MISSING {#ERROR_APP_TOKEN_PARAMETERS_MISSING}

The current API requires an App-Token header for using its methods.


### ERROR_NOT_DELETED {#ERROR_NOT_DELETED}

You must mark the item for deletion before actually deleting it


### ERROR_NOT_ALLOWED_IP {#ERROR_NOT_ALLOWED_IP}

We can't find an active client defined in configuration for your IP.  
Go to the GLPI Configuration > Setup menu and API tab to check IP access.


### ERROR_LOGIN_PARAMETERS_MISSING {#ERROR_LOGIN_PARAMETERS_MISSING}

One of theses parameter(s) is missing:
* login and password
* or user_token


### ERROR_LOGIN_WITH_CREDENTIALS_DISABLED {#ERROR_LOGIN_WITH_CREDENTIALS_DISABLED}

The GLPI setup forbid the login with credentials, you must login with your user_token instead.
See your personal preferences page or setup API access in GLPI main interface.


### ERROR_GLPI_LOGIN_USER_TOKEN {#ERROR_GLPI_LOGIN_USER_TOKEN}

The provided user_token seems invalid.  
Check your personal preferences page in GLPI main interface.


### ERROR_GLPI_LOGIN {#ERROR_GLPI_LOGIN}

We cannot login you into GLPI. This error is not relative to API but GLPI core.  
Check the user administration and the GLPI logs files (in files/_logs directory).


### ERROR_ITEMTYPE_NOT_FOUND_NOR_COMMONDBTM {#ERROR_ITEMTYPE_NOT_FOUND_NOR_COMMONDBTM}

You asked a inexistent resource (endpoint). It's not a predefined (initSession, getFullSession, etc) nor a GLPI CommonDBTM resources.

See this documentation for predefined ones or [List itemtypes](https://forge.glpi-project.org/embedded/glpi/annotated.html) for available resources


### ERROR_SQL {#ERROR_SQL}

We suspect an SQL error.  
This error is not relative to API but to GLPI core.  
Check the GLPI logs files (in files/_logs directory).


### ERROR_RANGE_EXCEED_TOTAL {#ERROR_RANGE_EXCEED_TOTAL}

The range parameter you provided is superior to the total count of available data.


### ERROR_GLPI_ADD {#ERROR_GLPI_ADD}

We cannot add the object to GLPI. This error is not relative to API but to GLPI core.  
Check the GLPI logs files (in files/_logs directory).


### ERROR_GLPI_PARTIAL_ADD {#ERROR_GLPI_PARTIAL_ADD}

Some of the object you wanted to add triggers an error.  
Maybe a missing field or rights.  
You'll find with this error a collection of results.


### ERROR_GLPI_UPDATE {#ERROR_GLPI_UPDATE}

We cannot update the object to GLPI. This error is not relative to API but to GLPI core.  
Check the GLPI logs files (in files/_logs directory).


### ERROR_GLPI_PARTIAL_UPDATE {#ERROR_GLPI_PARTIAL_UPDATE}

Some of the object you wanted to update triggers an error.  
Maybe a missing field or rights.  
You'll find with this error a collection of results.


### ERROR_GLPI_DELETE {#ERROR_GLPI_DELETE}

We cannot delete the object to GLPI. This error is not relative to API but to GLPI core.  
Check the GLPI logs files (in files/_logs directory).


### ERROR_GLPI_PARTIAL_DELETE {#ERROR_GLPI_PARTIAL_DELETE}

Some of the objects you want to delete triggers an error, maybe a missing field or rights.  
You'll find with this error, a collection of results.



## Servers configuration {#servers_configuration}

By default, you can use http://path/to/glpi/apirest.php without any additional configuration.

You'll find below some examples to configure your web server to redirect your http://.../glpi/api/ url to the apirest.php file.

### Apache Httpd

We provide in root .htaccess of glpi an example to enable api url rewriting.

You need to uncomment (removing #) theses lines:

```
#<IfModule mod_rewrite.c>
#   RewriteEngine On
#   RewriteCond %{REQUEST_FILENAME} !-f
#   RewriteCond %{REQUEST_FILENAME} !-d
#   RewriteRule api/(.*)$ apirest.php/$1
#</IfModule>
```

By enabling url rewriting, you could use api with this url : http://path/to/glpi/api/.
You need also to enable rewrite module in apache httpd and permit glpi's .htaccess to override server configuration (see AllowOverride directive).


### Nginx

This example of configuration was achieved on ubuntu with php7 fpm.

```
server {
   listen 80 default_server;
   listen [::]:80 default_server;

   # change here to match your glpi directory
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