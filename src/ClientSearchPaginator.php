<?php

namespace App;

class ClientSearchPaginator
{
    private int $itemsPerPage;
    private array $results;
    private int $page;
    private array $output = ["data" => [], "meta" => []];
    function __construct(array $results, int $page, $itemsPerPage = 20)
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
            "previous" => $previous,
            "itemsPerPage" => $this->itemsPerPage
        ]);

    }

    function paginate()
    {
        $start = ($this->page - 1) * $this->itemsPerPage;
        $data = $this->results["data"];
        $this->output["meta"]["length"] = 0;
        $current = 0;
        $currentHost = 0;
        $count = 0;
        foreach ($data as $arr) {
            foreach ($arr["data"] as $result){
                if($current < $start){
                    $current++;
                    continue;
                } else if ($count >= $this->itemsPerPage){
                    break;
                } else {
                    if(!isset($this->output["data"][$currentHost]["data"]))
                        $this->output["data"][$currentHost]["data"] = [];
                    array_push($this->output["data"][$currentHost]["data"], $result);
                    $this->output["data"][$currentHost]["meta"] = $arr["meta"];
                    $count++;
                }
                $current++;
            }
            $currentHost++;
        }
        $this->output["meta"]["length"] = $this->results["meta"]["length"];
        return $this->output;
    }
}