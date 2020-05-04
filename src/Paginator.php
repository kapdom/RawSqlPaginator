<?php

declare(strict_types=1);

namespace KapDom\RawQueryPaginator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use KapDom\RawQueryPaginator\Exceptions\RawPaginatorException;

class Paginator
{
    /**
     * @var array html title attribute content
     */
    protected $titles = [
        'first' => 'First',
        'previous' => 'Previous',
        'next' => 'Next',
        'last' => 'Last',
        'page' => 'Page',
        'current' => 'Current Page'
    ];

    /**
     * @var int Quantity items display per page
     */
    protected $itemsPerPage = 10;

    /**
     * @var int|null total items in DB
     */
    protected $totalItems;

    /**
     * @var int|null total pages
     */
    protected $totalPages;

    /**
     * @var object selected records from DB
     */
    public $records;

    /**
     * @var int|null current Page
     */
    protected $currentPage = 1;

    /**
     * @var int|null next available page
     */
    protected $nextPage;

    /**
     * @var string base url
     */
    protected $domain;

    /**
     * @var string full uri path with placeholder for page number
     */
    protected $fullUriPath;

    /**
     * @var string|null sql query
     */
    protected $dbQuery = null;

    /**
     * @var array PDO params
     */
    protected $dbParams;

    /**
     * @param Request $request
     * @param int $recordsPerPage
     * @param int $page
     * @param string $rawQuery raw sql query
     * @param array $params optional sql params
     *
     * @throws RawPaginatorException
     */
    public function __construct(Request $request)
    {
        $this->domain = url('/');
        $this->getRequestPage($request->path());
    }

    /**
     * Set manual uri for pages
     *
     * @param string $uri
     *
     * @return Paginator
     */
    public function setUri(string $uri) :self
    {
        $this->fullUriPath = $this->domain.$uri;

        return $this;
    }

    /**
     * Change default titles string
     *
     * @param array $titles
     *
     * @return Paginator
     */
    public function setTitles(array $titles) :self
    {
        $this->titles = array_replace ($this->titles , $titles );

        return $this;
    }

    /**
     * Render pages list with bootstrap classes
     *
     * @return string
     */
    public function renderPagesList() : ?string
    {
        if ($this->totalItems === 0) {

            return null;
        }

        return <<<"PAGINATION"
<nav class="pagination-nav">
    <ul class="pagination">
        <li class="page-item {$this->disableIfFirstOrLast(true)}">
            <a class="page-link" href="{$this->createUrlPath(1)}" title="{$this->titles['first']}" >&lt;&lt;</a>
        </li>
        <li class="page-item {$this->disableIfFirstOrLast(true)}">
            <a class="page-link" href="{$this->createUrlPath($this->currentPage-1)}" title="{$this->titles['previous']}" >&lt;</a>
        </li>
        {$this->generatePagesList()}
        <li class="page-item {$this->disableIfFirstOrLast()}">
            <a class="page-link" href="{$this->createUrlPath($this->currentPage+1)}" title="{$this->titles['next']}">&gt;</a>
        </li>
        <li class="page-item {$this->disableIfFirstOrLast()}">
            <a class="page-link" href="{$this->createUrlPath($this->totalPages)}" title="{$this->titles['last']}">&gt;&gt;</a>
        </li>
    </ul>
</nav>
PAGINATION;
    }

    /**
     * Generate list of pages
     *
     * @return string list of pages
     */
    public function generatePagesList() :string
    {
        $htmlString = '';
        for ($i = 1; $i <= $this->totalPages; $i++ ) {
            if( $i === $this->currentPage ){
                $htmlString .= '<li class="page-item active">
                                    <a class="page-link" href="" title="'.$this->titles['current'].'">'.$i.'<span class="sr-only"></span></a>
                                </li>';
            } else {
                $htmlString .= '<li class="page-item">
                                    <a class="page-link" href="'.$this->createUrlPath($i).'" title="'.$this->titles['page'].' '.$i.'">'.$i.'</a>
                               </li>';
            }
        }

        return $htmlString;
    }

    /**
     * disable button if this is last or first page
     *
     * @param bool $first
     *
     * @return null|string
     */
    private function disableIfFirstOrLast(bool $first = false): ?string
    {
        if($first){
            if ( $this->currentPage === 1 ) {

                return 'disabled';
            }
        } else if ( $this->currentPage === $this->totalPages ) {

            return 'disabled';
        }

        return null;
    }

    /**
     * Check if it last or first page
     *
     * @param bool $first
     *
     * @return bool
     */
    public function isFirstOrLastPage(bool $first = false): bool
    {
        if($first){
            if ( $this->currentPage === 1 ) {

                return true;
            }
        } else if ( $this->currentPage === $this->totalPages ) {

            return true;
        }

        return false;
    }

    /**
     * Get number of total pages
     *
     * @return int|null
     */
    public function totalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * Get number of total items / records in db
     *
     * @return int|null
     */
    public function totalItems(): ?int
    {
        return $this->totalItems;
    }

    /**
     * Get number of items per page
     *
     * @return int|null
     */
    public function getItemsPerPage(): ?int
    {
        return $this->itemsPerPage;
    }

    /**
     * Get number of next available page
     * If there is not next page return current page number
     *
     * @return int|null
     */
    public function getNextPage(): ?int
    {
        return $this->nextPage;
    }

    /**
     * Get current page number
     *
     * @return int|null
     */
    public function getCurrentPage(): ?int
    {
        return $this->currentPage;
    }

    /**
     * Retrieve from request url page number
     *
     * @param string $path
     */
    private function getRequestPage(string $path) :void
    {
        $pathArray = explode('/',$path);
        $pageNumber= end($pathArray);
        $this->currentPage = is_numeric($pageNumber) ? $pageNumber : 1;
        array_pop($pathArray);
        $this->fullUriPath = ($this->domain.'/'.implode('/',$pathArray)).'/%';
    }

    /**
     * Generate url with page number
     *
     * @param int $page
     *
     * @return string
     */
    private function createUrlPath(int $page) :string
    {
        return str_replace('%', $page, $this->fullUriPath);
    }

    /**
     * Check if current page is bigger then total available pages
     *
     * @throws RawPaginatorException
     */
    protected function checkMaxPagesNum() :void
    {
        if ($this->currentPage > $this->totalPages) {
            throw new RawPaginatorException('Page not exist');
        }
    }

    /**
     * Get quantity of all available records
     */
    private function getQuantityOfAllRecords() :void
    {
        $this->totalItems = count(DB::select($this->dbQuery, $this->dbParams));

        $this->calculateTotalPages();
    }

    /**
     * Calculate number of total pages
     */
    private function calculateTotalPages() :void
    {
        $this->totalPages = (int)(ceil($this->totalItems / $this->itemsPerPage));
        $this->nextPage = $this->currentPage === $this->totalPages ? $this->currentPage : $this->currentPage+1;
    }

    /**
     * Set number of records to skip
     */
    protected function setSkipNumber() :void
    {
        $this->dbQuery .= ' OFFSET :rawSkipNum';
        $this->dbParams['rawSkipNum'] = ($this->currentPage-1)*$this->itemsPerPage;
    }

    /**
     * Set number of records limit (per page)
     */
    protected function setLimitNumber() :void
    {
        $this->dbQuery .= ' LIMIT :rawLimitNum';
        $this->dbParams['rawLimitNum'] = $this->itemsPerPage;
    }

    /**
     * @param string $rawQuery
     * @param array $params
     *
     * @return Paginator
     */
    public function setQuery(string $rawQuery, array $params = []): self
    {
        $this->dbQuery = $rawQuery;
        $this->dbParams = $params;

        return $this;
    }

    /**
     * @param int $recordsPerPage
     * @param int|null $page
     *
     * @return Paginator
     *
     * @throws RawPaginatorException
     */
    public function setPageOptions(int $recordsPerPage = 10, int $page = null): self
    {
        if ($recordsPerPage === 0 || $page === 0) {
            throw new RawPaginatorException('"Records per page" or "page" can\'t be equal "0"');
        }

        if ($page !== null) {
            $this->currentPage = $page;
        }

        $this->itemsPerPage = $recordsPerPage;

        return $this;
    }

    /**
     * Get records from db
     *
     * @throws RawPaginatorException
     */
    public function executeQuery(): void
    {
        if ($this->dbQuery === null) {
            throw new RawPaginatorException('SQL query can\'t be empty. Please add query');
        }

        $this->getQuantityOfAllRecords();
        $this->setSkipNumber();
        $this->setLimitNumber();

        $this->records = DB::select($this->dbQuery, $this->dbParams);

        if(!empty($this->records)) {
            $this->checkMaxPagesNum();
        }
    }
}
