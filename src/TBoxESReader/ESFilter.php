<?php

namespace TBoxESReader;


class ESFilter implements Interfaces\ESFilterInterface
{
    public static function createQuery(array $Filter, array $Mapping = array())
    {
    	$query = array();
		if( empty($Filter) )
		{
			$query = array(
				'query' => array(
					'match_all' => (object)array()
				)
			);
		}
		else
		{

	    	$filters = array();
	    	$sort = array();
	    	foreach ($Filter as $filter) 
	    	{
	    		if( !empty($filter['name']) )
	    		{
	    			if( isset($filter['term']) ){
	    				$filters[$filter['name']] = $filter;
	    			}elseif( !empty($filter['direction']) ){
	    				$sort[] = array(
	    					$filter['name'] => $filter['direction']
	    				);
	    			}
	    		}	
	    	}

	    	if( empty($Mapping['properties']) )
	    	{
	    		$query = array(
	    			'query' => array(
	    				'bool' => array(
	    				)
	    			) 
	    		);
	    		foreach ($filters as $property => $filter) 
	    		{
	    			$boolType = 'must';
	    			$bool = static::createBool($filter, $boolType);
	    			if( !empty($bool) ){
	    				$query['query']['bool'][$boolType][] = $bool;
	    			}	
	    		}
	    	}
	    	else
	    	{
	    		$bool = static::query($Mapping['properties'], $filters, implode(' ', array_keys($filters)), '');
	    		if( !empty($bool) )
	    		{
		    		$query = array(
		    			'query' => array(
		    				'bool' => $bool
		    			)
		    		);
		    	}
	    	}
	    	if( !empty($sort) ){
	    		$query['sort'] = $sort;
	    	}
	    }

    	return $query;
    }


    private static function query(array $Mappings, array $Filter, $Check, $Level)
    {
    	$bools = array();
    	foreach ($Mappings as $property => $mapping) 
    	{
    		$level = empty($Level) ? $property : $Level . '.' . $property;
    		if( isset($Filter[$level]) )
    		{
    			$boolType = 'must';
    			$bool = static::createBool($Filter[$level], $boolType);
    			if( !empty($bool) ){
    				$bools[$boolType][] = $bool;
    			}
    		}elseif( strpos($Check, $level) !== false )
    		{
		    	if( isset($mapping['type']) && $mapping['type'] == 'nested' )
		    	{
		    		$bool = static::query($mapping['properties'], $Filter, $Check, $level);
		    		if( !empty($bool) )
		    		{
			    		$bools['must'][] =	array( 
							'nested' => array(
								'path' => $level,
				    			'query' => array(
				    				'bool' => $bool
				    			)
				    		)
		    			);
			    	}
		    	}elseif( !empty($mapping['properties']) )
		    	{

		    		$propBools = static::query($mapping['properties'],$Filter, $Check, $level);
		    		if( !empty($propBools) )
		    		{
			    		foreach ($propBools as $boolType => $propBool) 
			    		{
			    			if( !isset($bools[$boolType]) ){
			    				$bools[$boolType] = $propBool;
			    			}else
			    			{
				    			foreach ($propBool as $bool) {
		    						$bools[$boolType][] = $bool;
				    			}
				    		}
			    		}
			    	}
		    	}
			}
	    }

	    return $bools;
    }

    private static function createBool($Term, &$BoolType)
    {
    	$type = empty($Term['type']) ? 'equal' : $Term['type'];

        switch ($type)
        {
            case 'equal':
            	$BoolType = 'must';
            	if( is_array($Term['term']) )
            	{
            		$bool = array(
            			'terms' => array(
            				$Term['name'] => array_values($Term['term'])
            			)
            		);
            	}else
            	{
	            	$bool = array(
	    				'match' => array(
	    					$Term['name'] => $Term['term']
	    				)	
	    			);
	            }
                break;
            case 'notEqual':
            	$BoolType = 'must_not';
            	if( is_array($Term['term']) )
            	{
            		$bool = array(
            			'terms' => array(
            				$Term['name'] => array_values($Term['term'])
            			)
            		);
	            }else
	            {
	            	$bool = array(
	    				'match' => array(
	    					$Term['name'] => $Term['term']
	    				)	
	    			);        	
	            }
                break;
            case 'lessThan':
            	$BoolType = 'must';
            	$bool = array(
    				'range' => array(
    					$Term['name'] => array(
    						'lt' => $Term['term']
    					)
    				)	
    			);
                break;
            case 'greaterThan':
            	$BoolType = 'must';
            	$bool = array(
    				'range' => array(
    					$Term['name'] => array(
    						'gt' => $Term['term']
    					)
    				)	
    			);
                break;
            case 'lessThanOrEqual':
            	$BoolType = 'must';
            	$bool = array(
    				'range' => array(
    					$Term['name'] => array(
    						'lte' => $Term['term']
    					)
    				)	
    			);
                break;
            case 'greaterThanOrEqual':
            	$BoolType = 'must';
            	$bool = array(
    				'range' => array(
    					$Term['name'] => array(
    						'gte' => $Term['term']
    					)
    				)	
    			);
                break;
            case 'like':
            	$BoolType = 'must';
            	$bool = array(
    				'query_string' => array(
    					'query' => $Term['name'] . ':*' . $Term['term'] . '*',
    					'minimum_should_match' => '100%',
    				)	
    			);
                break;
            case 'notLike':
            	$BoolType = 'must_not';
            	$bool = array(
    				'query_string' => array(
    					'query' => $Term['name'] . ':*' . $Term['term'] . '*',
    					'minimum_should_match' => '100%',
    				)
    			);
                break;
            case 'between':
                if (is_array($Term['term']) && count($Term['term']) == 2)
                {
                    $minValue = reset($Term['term']);
                    $maxValue = end($Term['term']);
	            	$BoolType = 'must';
	            	$bool = array(
	    				'range' => array(
	    					$Term['name'] => array(
	    						'gte' => $minValue,
	    						'lte' => $maxValue
	    					)
	    				)	
	    			);
                }
                break;
        }

        return $bool;
    }

}
