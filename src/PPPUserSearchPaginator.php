<?php

namespace App;
use Exception;

class PPPUserSearchPaginator
{
    private $total = 0;
    private int $itemsPerPage;
    private array $results;
    private int $page;
    private array $output = ["data" => [], "meta" => []];
    function __construct(array $results, int $page, $itemsPerPage = 30)
    {
        $this->results = $results;
        $this->itemsPerPage = $itemsPerPage;
        $this->page = $page;
        $this->createMetadata();
    }

    private function createMetadata()
    {
        $maxPage = ceil($this->results["meta"]["length"] / $this->itemsPerPage);
        if ($this->page > $maxPage)
            $this->page = $maxPage;
        $next = $this->page < $maxPage;
        $previous = $this->page > 1;

        $this->output["meta"] = array_merge($this->output["meta"], [
            "currentPage" => $this->page,
            "maxPage" => $maxPage,
            "next" => $next,
            "previous" => $previous
        ]);

    }

    function paginate()
    {
        $start = ($this->page - 1) * $this->itemsPerPage;
        $data = $this->results["data"];
        foreach ($data as $arr) {
            $offset = 0;
            if (count($data) === 1 && $start !== 0)
                $offset = $start;
            $remaining = $this->itemsPerPage - $this->total;
            array_push($this->output["data"], [
                "data" => array_slice($arr["data"], $offset, $remaining),
                "meta" => $arr["meta"]
            ]);
            $this->total += count($arr["data"]);
        }

        $this->output["meta"]["length"] = $this->results["meta"]["length"];
        return $this->output;
    }
}