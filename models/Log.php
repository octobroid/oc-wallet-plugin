<?php namespace Octobro\Wallet\Models;

use Db;
use Model;
use Exception;
use ApplicationException;
use Octobro\Wallet\Classes\Cashback as CashbackHelper;

/**
 * Log Model
 */
class Log extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'octobro_wallet_logs';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [
        'related' => [],
        'owner' => []
    ];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function beforeCreate()
    {
        if (! $this->updated_amount) {
            $this->updated_amount = $this->owner->wallet_amount + $this->amount;

            $owner = $this->owner;
            $owner->wallet_amount = $this->updated_amount;
            $owner->save();
        }
    }

    public static function addCashback($order, $owner, $desc = null, $status = null)
    {
        Db::beginTransaction();

        if (!$order) throw new ApplicationException('Order not found');

        if (!$owner) throw new ApplicationException('Owner not found');

        try {
            $cashback = CashbackHelper::calculate($order);
            $prevWalletAmount = $owner->wallet_amount;

            $log = new static;
            $log->description = $desc;
            $log->previous_amount = $prevWalletAmount;
            $log->updated_amount = $prevWalletAmount + $cashback;
            $log->amount = $cashback;
            $log->owner_id = $owner->id;
            $log->owner_type = get_class($owner);
            $log->related_id = $order->id;
            $log->related_type = get_class($order);
            $log->status = $status;
            $log->save();

            $owner->wallet_amount = $prevWalletAmount + $cashback;
            $owner->save();
        } catch (Exception $e) {
            Db::rollback();
            throw new ApplicationException($e->getMessage());
        }

        Db::commit();
    }

    public static function createLog($related, $owner, $amount, $status = null, $desc = null)
    {
        Db::beginTransaction();

        if (!$related) throw new ApplicationException('Related not found');

        if (!$owner) throw new ApplicationException('Owner not found');

        try {
            $prevWalletAmount = $owner->wallet_amount;
            $updatedAmount = $prevWalletAmount + $amount;

            $log = new static;
            $log->owner_id = $owner->id;
            $log->owner_type = get_class($owner);
            $log->description = $desc;
            $log->previous_amount = $prevWalletAmount;
            $log->updated_amount = $updatedAmount;
            $log->amount = $amount;

            if (! is_null($related)) {
                $log->related_id = $related->id;
                $log->related_type = get_class($related);
            }

            $log->status = $status;
            $log->save();

            $owner->wallet_amount = $updatedAmount;
            $owner->save();
        } catch (Exception $e) {
            Db::rollback();
            throw new ApplicationException($e->getMessage());
        }

        Db::commit();
    }
}
