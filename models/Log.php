<?php namespace Octobro\Wallet\Models;

use Db;
use Model;
use Exception;
use ApplicationException;

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
    public $morphTo = [
        'related' => [],
        'owner' => []
    ];
    public $morphOne = [];
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

    public static function createLog($related, $owner, $ownerName, $amount, $status = null, $desc = null)
    {
        // if (!$related) throw new ApplicationException('Related not found');
        
        if (!$owner) throw new ApplicationException('Owner not found');
        
        if (!$amount || $amount == 0) return;
        
        try {
            Db::beginTransaction();

            $owner->reload();
            $prevWalletAmount = $owner->wallet_amount;
            $updatedAmount = $prevWalletAmount + $amount;

            $log = new self();
            $log->owner_name = $ownerName;
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

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

        return $log;
    }
}
