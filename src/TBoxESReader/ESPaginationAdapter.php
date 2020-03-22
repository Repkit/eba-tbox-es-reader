<?php
namespace TBoxESReader;

use Zend\Paginator\Adapter\AdapterInterface;

class ESPaginationAdapter implements AdapterInterface
{
	private $_client;
	private $_query;
	private $_countQuery;
	private $_rowCount;

	public function __construct($Client, $Query, $CountQuery = null)
	{
		if( empty($Query['body']) || empty($Query['index']) ){
			throw new \InvalidArgumentException('Invalid query');
		}
		$this->_client = $Client;
		$this->_query = $Query;
		if( !empty($CountQuery) ){
			$this->_countQuery = $CountQuery;
		}else{
			$this->_countQuery = $Query;
            unset($this->_countQuery['body']['sort']);
		}
	}

	/**
     * Returns an array of items for a page.
     *
     * @param  int $offset           Page offset
     * @param  int $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
    	$query = $this->_query;
		$query['body']['from'] = $offset;
		$query['body']['size'] = $itemCountPerPage;

    	$results = $this->_client->search($query);

    	return empty($results['hits']['hits']) ? [] : $results['hits']['hits'];
    }

    /**
     * Returns the total number of rows in the result set.
     *
     * @return int
     */
    public function count()
    {
        if ($this->_rowCount !== null) {
            return $this->_rowCount;
        }

        $result = $this->_client->count($this->_countQuery);
        $this->_rowCount = isset($result['count']) ? $result['count'] : 0;

        return $this->_rowCount;
    }
}