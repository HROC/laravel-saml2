<?php
declare(strict_types=1);
namespace Hroc\Saml2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Tenant
 *
 * @property int $id
 * @property string $request_id
 * @property string $redirect_url
 * @property integer $response_processed
 *
 * @package Hroc\Saml2\Models
 */
class Saml2LoginRequest extends Model
{
	use SoftDeletes;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'saml2_login_requests';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'request_id',
		'return_to',
	];

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = [];
}
