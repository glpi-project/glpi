# GLPI REST API :  Documentation

## Summary {#summary}

* [Glossary](#glossary)
* [Important](#important)
* [Init session](#init_session)
* [Kill session](#kill_session)
* [Change active entities](#change_active_entities)
* [Get my entities](#get_my_entities)
* [Get active entities](#get_active_entities)
* [Ghange active profile](#change_active_profile)
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


## Glossary {#glossary}

itemtype
:   a GLPI type, could be an asset, an itil or a configuration object, etc.
    This type must be a class who inherits CommonDTBM glpi class.
    See [List itemtypes](https://forge.glpi-project.org/embedded/glpi/annotated.html).

searchOption
:   a column identifier (integer) of an itemtype (ex: 1 -> id, 2 -> name, ...).
    See [List searchOptions](#list_searchoptions) endpoint.

JSON Payload
:   content of http Request in json format (http body)

query string
:   url parameters

Method
:   HTTP verbs to indicate the desired action to be performed on the identified ressource.
    See : https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol#Request_methods


## Important {#important}

* you should always precise a Content-Type header in your http calls.
   Currently, the api supports :
   - application/json
   - multipart/form-data (for files upload, see [Add item(s)](#add_items) endpoint.

* GET requests must have an empty body. You must pass all parameters in URL.
  Failing to do so will trigger an HTTP 400 response.

* You may pass your session_token in query string instead of payload for any verb.

* By default, sessions used in this API are read-only.
  Only Some methods have write access to session :
   - [initSession](#init_session)
   - [killSession](#kill_session)
   - [changeActiveEntities](#change_active_entities)
   - [changeActiveProfile](#change_active_profiles)

  You could pass an additional parameter "session_write=true" to bypass this default.
  This read-only mode allow to use this API with parallel calls.
  In write mode, sessions are locked and you client must wait the end of a call before the next one can execute.

* You can filter API access by enable the following parameters in glpi General Config (API tab) :
   - IPv4 range
   - IPv6 address
   - *app_token* parameter : if not empty, you must pass this parameter in all of your api calls


## Init session {#init_session}

* **URL**: api/initSession/
* **Description**: Request a session token to uses other api endpoints.
                   This endpoint can be optional by defining an user_token directly in [Users Configuration](../front/user.php).
* **Method**: GET
* **Parameters (query string)**
   - a couple *login* & *password* : 2 parameters to login with user authentication

      **OR**

   - an *user_token* defined in User Preference (See 'Remote access key')
* **Returns** :
   - 200 (OK) with an *session_token*
   - 400 (Bad Request) with a message indicating error in input parameter.
   - 401 (UNAUTHORIZED)

Examples usage (CURL) :

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/initSession?login=my_username&password=mystrongpassword'

< 200 OK
< {
   "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62"
}

$ curl -X GET \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/initSession?user_token=mystringapikey'

< 200 OK
< {
   "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62"
}
```

## Kill session {#kill_session}

* **URL**: api/killSession/
* **Description**: Destroy a session identified by a session token.
* **Method**: GET
* **Parameters (query string)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint. Mandatory
* **Returns**
   - 200 (OK)
   - 400 (Bad Request) with a message indicating error in input parameter.

Example usage (CURL) :

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/killSession?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'

< 200 OK
```


## Change active entities {#change_active_entities}

* **URL**: [api/changeActiveEntities/](changeActiveEntities/?entities_id=1&is_recursive=0&debug)
* **Description**: Change active entity to the entities_id one. see [getMyEntities](#get_my_entities) endpoint for possible entities
* **Method**: POST
* **Parameters (JSON Payload)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint . Mandatory
   - *entities_id* : (default 'all') ID of the new active entity ("all" => load all possible entities). Optional
   - *is_recursive* : (default false) Also display sub entities of the active entity.  Optional
* **Returns**
   - 200 (OK)
   - 400 (Bad Request) with a message indicating error in input parameter.

Example usage (CURL) :

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-d '{"entities_id": 1, "is_recursive": true , "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" }' \
'http://path/to/glpi/api/changeActiveEntities'

< 200 OK
```


## Get my entities {#get_my_entities}

* **URL**: [api/getMyEntities/](getMyEntities/?debug)
* **Description**: return all the possible entity of the current logged user (and for current active profile)
* **Method**: GET
* **Parameters (query string)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint. Mandatory
* **Returns**
   - 200 (OK) with an array of all entities (with id and name)
   - 400 (Bad Request) with a message indicating error in input parameter.

Example usage (CURL) :

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/getMyEntities?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'

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
* **Parameters (query string)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint. Mandatory
* **Returns**
   - 200 (OK) with an array with 3 keys :
      - *active_entity* : current set entity
      - *active_entity_recursive* : boolean, if we see sons of this entity
      - *active_entities* : array all active entities (active_entity and its sons)
   - 400 (Bad Request) with a message indicating error in input parameter.

Example usage (CURL) :

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/getMyEntities?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'

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
* **Description**: Change active profile to the profiles_id one. see [getMyProfiles](#get_my_profiless) endpoint for possible profiles
* **Method**: POST
* **Parameters (JSON Payload)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint. Mandatory
   - *profiles_id* : (default 'all') ID of the new active profile. Mandatory
* **Returns**
   - 200 (OK)
   - 400 (Bad Request) with a message indicating error in input parameter.

Example usage (CURL) :

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-d '{"profiles_id": 4, "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62"}' \
'http://path/to/glpi/api/changeActiveProfile'

< 200 OK
```


## Get my profiles {#get_my_profiles}

* **URL**: [api/getMyProfiles/](getMyProfiles/?debug)
* **Description**: Return all the profiles associated to logged user.
* **Method**: GET
* **Parameters (query string)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint. Mandatory
* **Returns**
   - 200 (OK) with an array of all profiles.
   - 400 (Bad Request) with a message indicating error in input parameter.

Example usage (CURL) :

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/getMyProfiles?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'

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
* **Description**: return the current (single) active profile
* **Method**: GET
* **Parameters (query string)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint. Mandatory
* **Returns**
   - 200 (OK) with an array representing current profile.
   - 400 (Bad Request) with a message indicating error in input parameter.

Example usage (CURL) :

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/getActiveProfile?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'

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
* **Parameters (query string)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint. Mandatory
* **Returns**
   - 200 (OK) with an array representing the php session.
   - 400 (Bad Request) with a message indicating error in input parameter.

Example usage (CURL) :

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/getFullSession?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'

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
* **Parameters (query string)**
   - *id* : unique identifier of the itemtype. Mandatory
   - *session_token*: session var provided by [initSession](#init_session) endpoint . Mandatory
   - *expand_dropdowns* (default: false): show dropdown name instead of id. Optional
   - *get_hateoas* (default: true): Show relation of item in a links attribute. Optional
   - *with_components* : Only for [Computer, NetworkEquipment, Peripheral, Phone, Printer], Optional.
   - *with_disks* : Only for Computer, retrieve the associated file-systems. Optional.
   - *with_softwares* : Only for Computer, retrieve the associated softwares installations. Optional.
   - *with_connections* : Only for Computer, retrieve the associated direct connections (like peripherals and printers) .Optional.
   - *with_networkports* : Retrieve all network connections and advanced network informations. Optional.
   - *with_infocoms* : Retrieve financial and administrative informations. Optional.
   - *with_contracts* : Retrieve associated contracts. Optional.
   - *with_documents* : Retrieve associated external documents. Optional.
   - *with_tickets* : Retrieve associated itil tickets. Optional.
   - *with_problems* : Retrieve associated itil problems. Optional.
   - *with_changes* : Retrieve associated itil changes. Optional.
   - *with_notes* : Retrieve Notes (if exists, not all itemtypes have notes). Optional.
   - *with_logs* : Retrieve historical. Optional.
* **Returns**
   - 200 (OK) with item data (Last-Modified header should contain the date of last modification of the item)
   - 401 (UNAUTHORIZED)
   - 404 (NOT FOUND)

Example usage (CURL) :

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/Computer/71?expand_drodpowns=true&session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'

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
* **Description**: Return a collection of rows of the desired itemtype
* **Method**: GET
* **Parameters (query string)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint . Mandatory
   - *expand_dropdowns* (default: false): show dropdown name instead of id. Optional
   - *get_hateoas* (default: true): Show relation of item in a links attribute. Optional
   - *only_id* (default: false):  keep only id in fields list. Optional
   - *range* (default: 0-50):  a string with a couple of number for start and end of pagination separated by a '-'. Ex : 150-200. Optional.
   - *sort* (default 1): id of searchoption to sort by. Optional.
   - *order* (default ASC): ASC - Ascending sort / DESC Descending sort. Optional.
* **Returns**
   - 200 (OK) with items data
   - 401 (UNAUTHORIZED)

   and theses headers :
      * *Content-Range* offset – limit / count
      * *Accept-Range* itemtype max

Example usage (CURL) :

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/Computer/?expand_drodpowns=true&session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'

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
* **Description**: Return a collection of rows of the desired sub_itemtype for the identified item
* **Method**: GET
* **Parameters (query string)**
   - id : unique identifier of the parent itemtype. Mandatory
   - *session_token*: session var provided by [initSession](#init_session) endpoint . Mandatory
   - *expand_dropdowns* (default: false): show dropdown name instead of id. Optional
   - *get_hateoas* (default: true): Show relation of item in a links attribute. Optional
   - *only_id* (default: false):  keep only id in fields list. Optional
   - *range* (default: 0-50):  a string with a couple of number for start and end of pagination separated by a '-'. Ex : 150-200. Optional.
   - *sort* (default 1): id of searchoption to sort by. Optional.
   - *order* (default ASC): ASC - Ascending sort / DESC Descending sort. Optional.
* **Returns**
   - 200 (OK) with items data
   - 401 (UNAUTHORIZED)

   and theses headers :
      * *Content-Range* offset – limit / count
      * *Accept-Range* itemtype max

Example usage (CURL) :

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/User/2/Log/?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'

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
* **Parameters (query string)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint. Mandatory
* **Returns**
   - 200 (OK) with all searchoptions of specified itemtype (format : searchoption_id: {option_content} )
   - 401 (UNAUTHORIZED)

Example usage (CURL) :

```bash
$ curl -X GET \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/listSearchOptions/Computer/?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'
< 200 OK
< {
    "common": "Characteristics",
    1: {
        "name": "Name",
        "table": "glpi_computers",
        "field": "name"
    },
    2: {
        "name": "ID",
        "table": "glpi_computers",
        "field": "id"
    },
    3: {
        "name": "Location",
        "table": "glpi_locations",
        "field": "completename"
    },
    91: {
        "name": "Building number",
        "table": "glpi_locations",
        "field": "building"
        },
    ...
}
```



## Search items {#search_items}

* **URL**: [api/search/:itemtype/](search/Computer/?debug)
* **Description**: Expose the GLPI searchEngine and combine criteria to retrieve a list of elements of specified itemtype.  
Note you can use 'AllAssets' itemtype to retrieve combined asset types.
* **Method**: GET
* **Parameters (query string)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint . Mandatory
   - *criteria*: array of criterion object to filter search.
      Optional.
      Each criterion object must provide :
         - *link*: (optional for 1st element) logical operator in [AND, OR, AND NOT, AND NOT].
         - *field*: id of searchoptions.
         - *searchtype*: type of search in [contains, equals, notequals, lessthan, morethan, under, notunder].
         - *value* : value to search.

      Ex :

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

   - *metacriteria* (optional): array of meta-criterion object to filter search.
                                 Optional.
                                 A meta search is a link with another itemtype (ex: Computer with softwares).
      Each meta-criterion object must provide :
         - *link*: logical operator in [AND, OR, AND NOT, AND NOT]. Mandatory
         - *itemtype*: second itemtype to link.
         - *field*: id of searchoptions.
         - *searchtype*: type of search in [contains, equals, notequals, lessthan, morethan, under, notunder].
         - *value* : value to search.

      Ex :

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

   - *sort* (default 1): id of searchoption to sort by. Optional.
   - *order* (default ASC): ASC - Ascending sort / DESC Descending sort. Optional.
   - *range* (default 0-50): a string with a couple of number for start and end of pagination separated by a '-'. Ex : 150-200.
                             Optional.
   - *forcedisplay*: array of columns to display (default empty = empty use display pref and search criteria).
                     Some columns will be always presents (1-id, 2-name, 80-Entity).
                     Optional.
   - *rawdata*: boolean for displaying raws data of Search engine of glpi (like SQL request, and full searchoptions)
* **Returns**
   - 200 (OK) with all rows data with this format :

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
   - 401 (UNAUTHORIZED)

   and theses headers :
      * *Content-Range* offset – limit / count
      * *Accept-Range* itemtype max

Example usage (CURL) :

```bash
curl -g -X GET \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/search/Monitor/?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62'\
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
* **Description**: Add an object (or multiple objects) to GLPI
* **Method**: POST
* **Parameters (JSON Payload)**
   - *session_token*: session var provided by [initSession](#init_session) endpoint . Mandatory
   - *input* : object with fields of itemtype to be inserted.
               You can add several items in one action by passing array of input object.
               Mandatory.

   **Important:**
      In case of 'multipart/data' content_type (aka file upload), you should insert your parameters into
      a 'uploadManifest' parameter. Theses serialized data must be a json string.

* **Returns**
   - 201 (OK) with id of added items.
   - 207 (Multi-Status) with id of added items and errors.
   - 400 (Bad Request) with a message indicating error in input parameter.
   - 401 (UNAUTHORIZED)
   - And additional header can be provided on creation success :
      - Location when adding a single item
      - Link on bulk addition

Examples usage (CURL) :

```bash
$ curl -X POST \
-H 'Content-Type: application/json' \
-d '{"input": {"name": "My single computer", "serial": "12345"}, "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" }' \
'http://path/to/glpi/api/Computer/'

< 201 OK
< Location: http://path/to/glpi/api/Computer/15
< {"id": 15}


$ curl -X POST \
-H 'Content-Type: application/json' \
-d '{"input": [{"name": "My first computer", "serial": "12345"}, {"name": "My 2nd computer", "serial": "67890"}, {"name": "My 3rd computer", "serial": "qsd12sd"}], "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" }' \
'http://path/to/glpi/api/Computer/'

< 201 OK
< Link: http://path/to/glpi/api/Computer/8,http://path/to/glpi/api/Computer/9
< [ {"id":"8"}, {"id":false}, {"id":"9"} ]
```



## Update item(s) {#update_items}

* **URL**: api/:itemtype/(:id)
* **Description**: update an object (or multiple objects) in GLPI
* **Method**: PUT
* **Parameters (JSON Payload)**
   - *id* : unique identifier of the itemtype passed in url. You **can skip** this param by passing it in input payload.
   - *session_token*: session var provided by [initSession](#init_session) endpoint . Mandatory
   - *input* : Array of objects with fields of itemtype to be updated.
               Mandatory.
               You **could provide** in each object a key named 'id' to identify item to update.
* **Returns**
   - 200 (OK) with update status for each item
   - 207 (Multi-Status) with id of added items and errors.
   - 400 (Bad Request) with a message indicating error in input parameter.
   - 401 (UNAUTHORIZED)

Example usage (CURL) :

```bash
$ curl -X PUT \
-H 'Content-Type: application/json' \
-d '{"input": {"otherserial": "xcvbn"}, "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" }' \
'http://path/to/glpi/api/Computer/10'

< 200 OK
[{"10":"true"}]


$ curl -X PUT \
-H 'Content-Type: application/json' \
-d '{"input": {"id": 11,  "otherserial": "abcde"}, "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" }' \
'http://path/to/glpi/api/Computer/'

< 200 OK
[{"11":"true"}]


$ curl -X PUT \
-H 'Content-Type: application/json' \
-d '{"input": [{"id": 16,  "otherserial": "abcde"}, {"id": 17,  "otherserial": "fghij"}], "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" }' \
'http://path/to/glpi/api/Computer/'

< 200 OK
[{"8":"true"},{"2":"true"}]
```



## Delete item(s) {#delete_items}

* **URL**: api/:itemtype/(:id)
* **Description**: delete an object in GLPI
* **Method**: DELETE
* **Parameters (query string)**
   - *id* : unique identifier of the itemtype passed in url. You **can skip** this param by passing it in input payload.
      OR
   - *input* Array of id who need to be deleted. This param is passed by payload.

   id param has precedence over input payload.

   - *session_token*: session var provided by [initSession](#init_session) endpoint . Mandatory
   - *force_purge* : boolean, if itemtype have a dustbin, you can force purge (delete finally).
                     Optional.
   - *history* : boolean, default true, false to disable saving of deletion in global history.
                 Optional.
* **Returns**
   - 200 (OK) *in case of multiple deletion*
   - 204 (No Content) *in case of single deletion*
   - 207 (Multi-Status) with id of deleted items and errors.
   - 400 (Bad Request) with a message indicating error in input parameter
   - 401 (UNAUTHORIZED)

Example usage (CURL) :

```bash
$ curl -X DELETE \
-H 'Content-Type: application/json' \
'http://path/to/glpi/api/Computer/16?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62&force_purge=true'

< 204 OK


$ curl -X DELETE \
-H 'Content-Type: application/json' \
-d '{"input": {"id": 11, "force_purge": true}, "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" }' \
'http://path/to/glpi/api/Computer/'

< 200 OK
[{"11":"true"}]


$ curl -X DELETE \
-H 'Content-Type: application/json' \
-d '{"input": [{"id": 16}, {"id": 17}], "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62" }' \
'http://path/to/glpi/api/Computer/'

< 207 OK
[{"16":"true"},{"17":"false"}]
```

