<?php namespace Octobro\Wallet\Models;

use Db;
use Event;
use Model;
use Exception;
use Carbon\Carbon;

/**
 * Usage Model
 */
class Usage extends Model
{
    use \October\Rain\Database\Traits\Validation;

    const STATUS_HOLD = 'hold';
    const STATUS_USED = 'used';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'octobro_wallet_usages';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = [];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'used_at',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [
        'owner' => [],
    ];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function beforeCreate()
    {
        $this->status      = self::STATUS_HOLD;
    }

    public function use()
    {
        // If already used
        if ($this->status == self::STATUS_USED) {
            return;
        }

        try {
            Db::beginTransaction();

            $this->status  = self::STATUS_USED;
            $this->used_at = Carbon::now();
            $this->save();

            $invoice = null;

            Log::createLog($invoice, $this->owner, null, $this->amount, null, 'Wallet Usage for Invoice #' . $invoice->id);

            Event::fire('octobro.wallet.afterWalletUsed', [$this]);

            Db::commit();
        }
        catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

}
