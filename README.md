# Raw SQL Paginator
SQL raw query paginator for Laravel

# Pretty Pagination - Laravel
This class let you add pagination for raw SQL queries ang generate pretty urls. 
## Requirements

* Laravel 6+

## Usage

#####Controller

```php
public function index(Request $request, int $page)
    {
        $itemsPerPage = 10;
        $rawSqlQuery = 'SELECT * FROM articles WHERE author_id = :authorId';
        $params = ['authorId' => 1]; 
        $paginator = new Paginator($request);
        $paginator->setQuery($rawSqlQuery, $params)
                  ->setPageOptions($itemsPerPage, $page)
                  ->executeQuery();

        return view('post.index',compact('paginator'));
    }
```
Method `setPageOptions` is optional. Paginator try to get page number from query if it last value.
`$itemsPerPage` is set default to 10. You can only set `items per page` value.

`!Do not use LIMIT or OFFSET in highest "SELECT" level in query!`

#####Blade file
Display pages list
```php
{!! $paginator->renderPagesList() !!}
```
Display records from DB


```php
@foreach($paginator->records as $post)
    <p>{{$post}}</p>
@endforeach
```

## OPTIONS & WARNINGS

#### Display pages list


Output hmtl pages list is optimized for `Bootstrap 4.*` but of course you can modify output look by adding own style in css file.

You don't want to use `renderPagesList()` method? No problem.
PrettyPagination provide necessary set of methods and properties, to create own pages list in `blade fi

#### Public methods 
`` itemsPerPage() `` - How many items per page is displayed

``totalPages()`` - Number of available pages

``totalItems()`` - Quantity of all items / records from DB

``nextPage()``  - Next available page

``currentPage()`` - Current Page

``isFirstOrLastPage(bool)`` - Check if current page is first or last. Pass as argument **true** to check if first page or **false** to check if last page.

#### Links generator
`prettyPagination` automatically get path for links in pages list if url structure is as follow:
```html
Correct - path number is the last one.
domain.com\path\2
domain.com\path\path\path\2
```

If you use any other path structure like:

```html
domain.com\path\2\path
domain.com\path\2\path\14
```

Then in controller before you call `$prettyPagination->get()`, set correct pages link:

```php

        
$paginator->setUri('/posts/%/page');
      
```

Use "Percent sign - %" as a placeholder for page number.

#### Default page


#### Title value

All pages list links has default title value as follow:

```php
$titles = [
       'first' => 'First',
       'previous' => 'Previous',
       'next' => 'Next',
       'last' => 'Last',
       'page' => 'Page',
       'current' => 'Current page'
        ];
```

If you would like to change some of them or all use `setTitles` method.

```php
    $paginator->setTitle(
        [
            $key => $value
        ])
```
