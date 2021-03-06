<?php
namespace rosasurfer\xtrade\model;

use rosasurfer\db\orm\DAO;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use const rosasurfer\db\orm\meta\FLOAT;
use const rosasurfer\db\orm\meta\INT;
use const rosasurfer\db\orm\meta\STRING;


/**
 * DAO zum Zugriff auf OpenPosition-Instanzen.
 */
class OpenPositionDAO extends DAO {


    /**
     * {@inheritdoc}
     */
    public function getMapping() {
        static $mapping; return $mapping ?: ($mapping=$this->parseMapping([
            'connection' => 'mysql',
            'table'      => 't_openposition',
            'class'      => OpenPosition::class,
            'properties' => [
                ['name'=>'id',          'type'=>INT,    'primary'=>true],      // db:int
                ['name'=>'created',     'type'=>STRING,                ],      // db:datetime
                ['name'=>'version',     'type'=>STRING, 'version'=>true],      // db:datetime

                ['name'=>'ticket',      'type'=>INT,                   ],      // db:int
                ['name'=>'type',        'type'=>STRING,                ],      // db:string
                ['name'=>'lots',        'type'=>FLOAT,                 ],      // db:decimal
                ['name'=>'symbol',      'type'=>STRING,                ],      // db:string
                ['name'=>'openTime',    'type'=>STRING,                ],      // db:datetime
                ['name'=>'openPrice',   'type'=>FLOAT,                 ],      // db:decimal
                ['name'=>'stopLoss',    'type'=>FLOAT,                 ],      // db:decimal
                ['name'=>'takeProfit',  'type'=>FLOAT,                 ],      // db:decimal
                ['name'=>'commission',  'type'=>FLOAT,                 ],      // db:decimal
                ['name'=>'swap',        'type'=>FLOAT,                 ],      // db:decimal
                ['name'=>'magicNumber', 'type'=>INT,                   ],      // db:int
                ['name'=>'comment',     'type'=>STRING,                ],      // db:string
            ],
            'relations' => [
                ['name'=>'signal', 'assoc'=>'many-to-one', 'type'=>Signal::class, 'column'=>'signal_id'],
            ],
        ]));
    }


    /**
     * Gibt die offenen Positionen des angegebenen Signals zurueck.
     *
     * @param  Signal $signal      - Signal
     * @param  bool   $assocTicket - ob das Ergebnisarray assoziativ nach Tickets organisiert werden soll (default: nein)
     *
     * @return OpenPosition[] - Array von OpenPosition-Instanzen, aufsteigend sortiert nach {OpenTime,Ticket}
     */
    public function listBySignal(Signal $signal, $assocTicket=false) {
        if (!$signal->isPersistent()) throw new InvalidArgumentException('Cannot process non-persistent '.get_class($signal));
        return $this->listBySignalAlias($signal->getAlias(), $assocTicket);
    }


    /**
     * Gibt die offenen Positionen des angegebenen Signals zurueck.
     *
     * @param  string $alias       - Signalalias
     * @param  bool   $assocTicket - ob das Ergebnisarray assoziativ nach Tickets organisiert werden soll (default: nein)
     *
     * @return OpenPosition[] - Array von OpenPosition-Instanzen, aufsteigend sortiert nach {OpenTime,Ticket}
     */
    public function listBySignalAlias($alias, $assocTicket=false) {
        if (!is_string($alias)) throw new IllegalTypeException('Illegal type of parameter $alias: '.getType($alias));

        $alias = $this->escapeLiteral($alias);

        $sql = "select o.*
                      from :Signal       s
                      join :OpenPosition o on s.id = o.signal_id
                      where s.alias = $alias
                      order by o.opentime, o.ticket";
        /** @var OpenPosition[] $results */
        $results = $this->findAll($sql);

        if ($assocTicket) {
            foreach ($results as $i => $position) {
                $results[(string) $position->getTicket()] = $position;
                unset($results[$i]);
            }
        }
        return $results;
    }


    /**
     * Gibt zu einem angegebenen Ticket die offene Position zurueck.
     *
     * @param  Signal $signal - Signal
     * @param  int    $ticket - Ticket
     *
     * @return OpenPosition
     */
    public function getByTicket(Signal $signal, $ticket) {
        if (!$signal->isPersistent()) throw new InvalidArgumentException('Cannot process non-persistent '.get_class($signal));
        if (!is_int($ticket))         throw new IllegalTypeException('Illegal type of parameter $ticket: '.getType($ticket));

        $signal_id = $signal->getId();

        $sql = "select *
                      from :OpenPosition o
                      where o.signal_id = $signal_id
                         and o.ticket   = $ticket";
        return $this->find($sql);
    }
}
