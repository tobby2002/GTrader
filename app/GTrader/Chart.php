<?php

namespace GTrader;

abstract class Chart extends Skeleton {
    use HasCandles;

    
    protected $_strategy = null;
    
    public abstract function toHtml(array $params = []);
    
    public function __construct(array $params = [])
    {
        if (isset($params['candles']))
        {
            $this->setCandles($params['candles']);
            unset($params['candles']);
        }
        if (isset($params['strategy']))
        {
            $this->setStrategy($params['strategy']);
            unset($params['strategy']);
        }
        parent::__construct($params);
    }
    
        
    public function getStrategy()
    {
        return $this->_strategy;
    }
    
    public function setStrategy(&$strategy)
    {
        $this->_strategy = $strategy;
        return $this;
    }
    

    public function getIndicators()
    {
        return array_merge($this->getCandles()->getIndicators(),
                            $this->getStrategy()->getIndicators());
    }
    

    public function getIndicatorsVisibleSorted()
    {
        $ind_sorted = [];
        $all_ind = $this->getIndicators();
       
        foreach ($all_ind as $ind)
        {
            $func = false;
            if (true === $ind->getParam('display.visible'))
            {
                $func = 'array_unshift';
                if ('right' === $ind->getParam('display.y_axis_pos'))
                    $func = 'array_push';
            }
            if ($func)
                $func($ind_sorted, $ind);
        }
        return $ind_sorted;
    }
    

}