<?php namespace Octobro\Wallet\Models;

use Db;
use Model;
use Exception;
use ApplicationException;
use Cashback as CashbackHelper;
use RainLab\User\Models\User;

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
    public $belongsTo = [
        'user' => 'RainLab\User\Models\User'
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [
        'order' => [
            'OpenTrip\Commerce\Models\Order',
            'name' => 'related'
        ]
    ];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function beforeCreate()
    {
        if (! $this->updated_amount) {
            $this->updated_amount = $this->user->wallet_amount + $this->amount;

            $user = $this->user;
            $user->wallet_amount = $this->updated_amount;
            $user->save();
        }
    }

    public static function addCashback($order, $desc = null, $status = null)
    {
        Db::beginTransaction();

        try {
            $user = User::find($order->user_id);
            $cashback = CashbackHelper::calculate($order);
            $prevWalletAmount = $user->wallet_amount;

            $log = new static;
            $log->user_id = $order->user_id;
            $log->description = $desc;
            $log->previous_amount = $prevWalletAmount;
            $log->updated_amount = $prevWalletAmount + $cashback;
            $log->amount = $cashback;
            $log->related_id = $order->id;
            $log->related_type = get_class($order);
            $log->status = $status;
            $log->save();

            $user->wallet_amount = $prevWalletAmount + $cashback;
            $user->save();
        } catch (Exception $e) {
            Db::rollback();
            throw new ApplicationException($e->getMessage());
        }

        Db::commit();
    }

    public static function createLog($related, $user, $amount, $status = null, $desc = null)
    {
        Db::beginTransaction();

        try {
            $prevWalletAmount = $user->wallet_amount;
            $updatedAmount = $prevWalletAmount + $amount;

            $log = new static;
            $log->user_id = $user->id;
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

            $user->wallet_amount = $updatedAmount;
            $user->save();
        } catch (Exception $e) {
            Db::rollback();
            throw new ApplicationException($e->getMessage());
        }

        Db::commit();
    }
}
