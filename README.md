# Laravel API Builder

A Laravel database and API generator.

Author: Thierry DE LAPEYRE

## Features

- Generate table migration file, controller file, model file and add the routes
- One artisan command to generate everything with logs
- Fetch the foreign table record or list of records linked to your table
- Validate fields and return comprehensive errors
- Separate the table constraints and sort the table creation to prevent migration errors

## Requires

- PHP >= 7
- Laravel >= 5.5

## Install

``` shell
composer require grabmy/laravel-api-builder
```

Additional artisan commands must appear when you type:

``` shell
php artisan list
```

The artisan command is "make:api" and you must give the path of a JSON configuration file as a parameter. 
The optional verbose -v parameter can show more logs.

``` shell
php artisan make:api ./api.json -v
```

## Quick start

Create a configuration file "api.json" in the root folder of your Laravel project:

``` JSON
{
  "tables": {
    "article": {
      "fields": {
        "id": "increments",
        "name": "string|required|min:2",
        "description": "string:200|nullable",
        "published": "bool|default:0"
      },
      "api": {
        "endpoint": "article",
        "methods": ["GET", "POST", "PUT", "DELETE"]
      }
    }
  }
}
```

In this example, we define a simple table and API behavior. A table will be created with an integer incrementing "id", a string "name", a string "description" with length 200 and a boolean "published" with default false (0).

To boostrap your table migration and create all the files for the API in your project, you must run a command.

> **WARNING**: If you already have a model, controller or migration file with the same filenames, the files will be overwritten.

``` shell
php artisan make:api ./api.json
```

If everything works, you can see the files generated in green.
You still have to run the migration that creates the table in your database.

> **WARNING**: the basic migrate command will also destroy and recreate other tables you have in the migration of your project

``` shell
php artisan migrate fresh
```

Assuming your project is accessible at "http://127.0.0.1:8000/", you can now:

- Get all content of the table article with:<br/>
  GET http://127.0.0.1:8000/api/article

- Get just one item of the table article by its id:<br/>
  GET http://127.0.0.1:8000/api/article/{id}

- Create an article with a name, description and published values in JSON body:<br/>
  POST http://127.0.0.1:8000/api/article

- Update an article with the modified values in JSON body:<br/>
  PUT http://127.0.0.1:8000/api/article/{id}

- Delete an article:<br/>
  DELETE http://127.0.0.1:8000/api/article/{id}

## Configuration file

The configuration file contains the definition of the tables and the behavior of the API. Each tables have their fields definition.

Multiple tables:

``` JSON
{
  "tables": {
    "article": {
      ...
    },
    "category": {
      ...
    },
    "user": {
      ...
    }
  }
}
```

Multiple fields for each table:

``` JSON
{
  "tables": {
    "article": {
      "fields": {
        "id": "integer",
        "name": "string",
        "description": "string",
        "content": "text"
      }
    }
  }
}
```

You can have multiple options for a field. Each options are separated by the character pipe "|". The main option is the type of the field, which I usually put first (it's easier to find).

Multiple options:

``` JSON
{
  "tables": {
    "product": {
      "fields": {
        "id": "uuid|primary",
        "name": "string|required",
        "description": "text|nullable",
        "quantity": "integer"
      }
    }
  }
}
```

An option can have some additional parameters. Each parameters are separated by the character semicolon ":".

Options with multiple parameters:

``` JSON
{
  ...
        "description": "string:200",
        "published": "bool|default:0",
        "category_id": "integer|link:category:id|nullable"
  ...
  }
```

## The available requests

### Getting the record list

Url: GET /api/endpoint

In our example if we have two articles, GET http://127.0.0.1:8000/api/article will result:

Status code: 200

Body:

``` JSON
[
  {
    "id": 1,
    "name": "First article",
    "description": "This is my first article",
    "content": "<p>...</p>"
  },
  {
    "id": 2,
    "name": "Aviation and aerospace",
    "description": "AS9102 is the Aerospace Standard",
    "content": "<p>...</p>"
  }
]
```

### Getting one record

Url: GET /endpoint/{id}

In our example, GET http://127.0.0.1:8000/api/article/1 will result:

Status code: 200

Body:

``` JSON
{
  "id": 1,
  "name": "First article",
  "description": "This is my first article",
  "content": "<p>...</p>"
}
```

### Creating a record

@TODO

### Updating a record

@TODO

### Deleting a record

@TODO

### Errors

@TODO

## Field types

| Type       | Description                          | Parameters
| ---------- | ------------------------------------ | ---------------
| string     | Just a string                        | optional length
| text       | Long text                            |
| int        | Integer                              |
| bool       | Boolean                              |
| float      | A float number                       |
| uuid       | An UUID                              |
| increments | An incrementing integer              |
| list       | A list of records from another table | Table, field

### UUID

If the field has an UUID type and is primary, the API will generate an UUID on POST creation.
If the UUID field is not primary, on POST creation and PUT update, the API will automatically check if the passed string is a valid UUID.

### Increments

@TODO

### List

@TODO

## Other options

| Option   | Description                               | Parameters
| -------- | ----------------------------------------- | -------------
| max      | Check the maximum length                  | Length
| min      | Check the minimum length                  | Length
| type     | Check the type                            | Type
| required | Check if a value exists                   |
| nullable | Field value can be null and optional      |
| default  | Set the default value                     | Value
| primary  | Set the field as primary key              |
| omit     | Don't return the field value in API       |
| link     | Link the foreign key to another table     | table, field
| as       | Return this field value with another name |

### Type option

Return an error if the JSON don't have the correct type on POST creation and PUT update. List of types:

- integer
- float
- string
- UUID

``` JSON
{
  ...
    "name": "string|type:string",
    "number": "integer|type:integer",
    "valid": "boolean|type:boolean"
  ...
}
```

### Link and as options

@TODO

## API configuration

### Endpoint

@TODO

### Fetch

The fetch feature is a powerfull feature that allows you to tell the API to fetch records or array of records from other tables.

In the fetchable parameter:

- Set a field value to true will fetch this field record and every records in it
- Set a field value to false will only fetch the field record but not the records in it
- No field or set the field to an empty object will not fetch the field record

The default value for the fetchable parameter is true.

For example with this configuration:

``` JSON
{
  "tables": {
    "article": {
      "fields": {
        "id": "increments",
        "name": "string|required|min:2",
        "category_id": "integer|link:category:id|as:category",
        "websites": "list:website:article_id"
      },
      "api": {
        "endpoint": "article",
        "fetchable": true
      }
    }
  }
}
```

Here, we have a link to a record of the "category" table and a list of records of the "website" table.
Setting "fetchable" to true will fetch all fields linked to our table (type "list" or option "link").

The "as" option is mandatory with the "link" option. It means the category record will be placed in a field with the name "category" instead of "category_id". The "websites" field is a list, so it will get an array of records from the "website" table where article_id is set to the value of the id of the article.

Now, when we are getting one article record:

``` JSON
{
  "id": 1,
  "name": "First article",
  "category": {
    "id": 5,
    "name": "Dummy",
    "tag_id": 3
  },
  "websites": [
    {
      "id": 1,
      "url": "http://www.mywebsite.com/"
    },
    {
      "id": 2,
      "url": "https://www.google.com/"
    }
  ]
}
```

If we set the fetchable parameter to false, we get the article record without fetching any linked record in it.

``` JSON
{
  ...
        "fetchable": false
  ...
}
```

Our article from the API becomes flat:

``` JSON
{
  "id": 1,
  "name": "First article",
  "category_id": 5,
}
```

We don't see the "websites" field because it is not saved field in the database, so it will be available only if the websites records are fetched. We see now the "category_id" field which is not filtered out.

You can also set the fetchable parameter to just fetch "category" in our article and fetch everything in it but not fetching "websites". So the "tag_id" the category record becomes a record as we can imagine it's a link to a tag table.

``` JSON
{
  ...
        "fetchable": {
          "category": true
        }
  ...
}
```

The category is fetched and every field that can be fetched in it:

``` JSON
{
  "id": 1,
  "name": "First article",
  "category": {
    "id": 5,
    "name": "Dummy",
    "tag": {
      "code": "DUMMY",
      "searchable": true
    }
  }
}
```

You can set the API result as you want and go as deep as you like without creating an infinite loop.
Be aware that the more you fetch records from other tables, the slower your API will be, especially if you fetch in an array.

### Fetch POST and PUT option

@TODO

### Methods

@TODO

## What this API generator doesn't do

- Table with multiple fields as primary key
- Change the path of generated files

## TODO

- Save and restore database in JSON files
- Add an error on wrong api methods
- Add an error if "link" dont have an "as" option
- Add type check for email, ip, url
- Add field type json
- Add where clause to list and link fields
- Change migration, model, controller, api route path in config
- Change namespace and class extends in config

## DONE

- Make a default sort number for tables
