<?php namespace Octobro\Wallet\Models;

use Db;
use ApplicationException;
use Carbon\Carbon;
use Exception;
use Model;
use Responsiv\Pay\Models\Invoice;
use Responsiv\Pay\Models\InvoiceItem;

/**
 * TopUp Model
 */
class TopUp extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'octobro_wallet_top_ups';

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
        'paid_at',
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

    public function createInvoice(\RainLab\User\Models\User $user = null)
    {
        $invoice = new Invoice([
            'related' => $this->owner,
        ]);

        if ($user) {
            $invoice->user       = $user;
            $invoice->first_name = $user->name;
            $invoice->email      = $user->email;
            $invoice->phone      = isset($user->phone) ? $user->phone : null;
        }

        $invoice->save();

        $invoiceItem = new InvoiceItem([
            'description' => 'Wallet Top Up',
            'quantity'    => 1,
            'price'       => $this->amount,
            'related'     => $this,
        ]);

        $invoice->items()->save($invoiceItem);
        
        $invoice->save();
        
        return $invoice;
    }

    public function markAsPaid()
    {
        if ($this->is_paid) {
            throw new ApplicationException('This top up is already paid.');
        }

        try {
            Db::beginTransaction();

            Log::createLog($this, $this->owner, null, $this->amount, null, 'Top Up');
            
            $this->is_paid = true;
            $this->paid_at = Carbon::now();
            $this->save();
            Db::commit();
        }
        catch (Exception $ex) {
            Db::rollBack();
            throw $ex;
        }
    }
}
