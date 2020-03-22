<?php
namespace TBoxESReader;

class ESReader implements Interfaces\ESReaderInterface
{
	private $_client;

	public function __construct($Hosts, $Retries = 2, $Client = null)
	{
	    if( !empty($Client) ){
	        $this->_client = $Client;
	    }else{
    		$this->_client = \Elasticsearch\ClientBuilder::create()
    			->setHosts($Hosts)
                ->setRetries($Retries)
                ->build();
	    }
	}

	public function getByIndexAndId($Index, $Id)
	{
		$results = $this->_client->search([
            'index' => $Index,
            'body' => [
            	'query' => [
            		'match' => [
            			'_id' => $Id
            		]
            	]
            ]
		]);

		$results = empty($results['hits']['hits']) ? [] : $results['hits']['hits'];
		$current = reset($results);

		return $current;
	}

	public function getAllByIndex($Index, $Filter = [])
	{
		$mapping = $this->getMapping($Index);
		$query = ESFilter::createQuery($Filter, $mapping);

		return new ESPaginationAdapter($this->_client,[
            'index' => $Index,
            'body' => $query
		]);
	}

	private function getMapping($Index)
	{

		$response = $this->_client->indices()->getMapping([
		    'index' => $Index,
		]);
		$response = reset($response);
		$mapping = reset($response['mappings']);

		return empty($mapping) ? [] : $mapping;
	}


}
