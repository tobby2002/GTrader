<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Series;

class FannSignals extends Indicator
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->allowed_owners = ['GTrader\\Series'];
    }


    public function createDependencies()
    {
        $strategy = $this->getOwner()->getStrategy();
        if (!$strategy) {
            return $this;
        }
        if (!$strategy->isClass('GTrader\\Strategies\\Fann')) {
            return $this;
        }
        if (is_object($strategy)) {
            if ($ind = $strategy->getPredictionIndicator()) {
                $ind->addRef($this->getSignature());
            }
        }
        return $this;
    }


    public function calculate(bool $force_rerun = false)
    {
        $candles = $this->getCandles();

        $signature = $candles->key($this->getSignature());

        $strategy = $this->getOwner()->getStrategy();
        if (!$strategy) {
            return $this;
        }
        if (!$strategy->isClass('GTrader\\Strategies\\Fann')) {
            return $this;
        }

        $indicator = $strategy->getPredictionIndicator();
        $indicator->addRef($this->getSignature());
        $indicator->checkAndRun($force_rerun);
        $indicator_sig = $candles->key($indicator->getSignature());

        $last = ['time' => 0, 'signal' => ''];
        $candles_seen = 0;

        //$trade_indicator = 'open';
        //$trade_indicator = 'ohlc4';
        //$this->candles->ohlc4()->reset();

        $spitfire = $strategy->getParam('spitfire');
        $long_threshold = $strategy->getParam('long_threshold');
        $short_threshold = $strategy->getParam('short_threshold');
        if ($long_threshold == 0 || $short_threshold == 0) {
            throw new \Exception('Threshold is zero');
        }
        $min_distance = $strategy->getParam('min_trade_distance');
        $long_source = $strategy->getParam('long_source', 'open');
        $short_source = $strategy->getParam('short_source', 'open');
        $resolution = $candles->getParam('resolution');
        $num_input = $strategy->getNumInput();

        $candles->reset(true);
        while ($candle = $candles->next()) {
            if ($force_rerun && isset($candle->$signature)) {
                unset($candle->$signature);
            }

            $candles_seen++;
            if ($candles_seen < $num_input) {
                // skip trading while inside the first sample
                continue;
            }
            if (isset($candle->$indicator_sig)) {
                // skip trade if last trade was recent
                if ($last['time'] >= $candle->time - $min_distance * $resolution) {
                    continue;
                }

                if ($candle->$indicator_sig >
                    $candle->open + $candle->open / $long_threshold &&
                    ($last['signal'] != 'long' || $spitfire)) {
                    $price = 'ohlc4' === $long_source ?
                        Series::ohlc4($candle) :
                        $candle->$long_source;
                    $candle->$signature = [
                        'signal' => 'long',
                        'price' => $price,
                    ];
                    $last = [
                        'time' => $candle->time,
                        'signal' => 'long',
                    ];
                    continue;
                }

                if ($candle->$indicator_sig <
                    $candle->open - $candle->open / $short_threshold &&
                    ($last['signal'] != 'short' || $spitfire)) {
                    $price = 'ohlc4' === $short_source ?
                        Series::ohlc4($candle) :
                        $candle->$short_source;
                    $candle->$signature = [
                        'signal' => 'short',
                        'price' => $price,
                    ];
                    $last = [
                        'time' => $candle->time,
                        'signal' => 'short',
                    ];
                }
            }
        }
        //dd($candles);
        //error_log('strategy signals: '.count($signals));
        return $this;
    }
}
