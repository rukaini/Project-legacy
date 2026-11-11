<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    // Specify the table name (plural)
    protected $table = 'organizations';
    
    // Set the primary key
    protected $primaryKey = 'OrganizationID';
    
    // Disable auto-incrementing since we're using string IDs
    public $incrementing = false;
    
    // Set the key type to string
    protected $keyType = 'string';

    // Table has no created_at/updated_at columns
    public $timestamps = false;

    // Define fillable fields
    protected $fillable = [
        'OrganizationID',
        'OrganizationName',
        'OrganizationAddress',
        'RegisterDate',
        'PhoneNumber'
    ];

    // Define date casts
    protected $casts = [
        'RegisterDate' => 'date',
    ];

    // Relationship with schools
    public function schools()
    {
        return $this->hasMany(School::class, 'OrganizationID', 'OrganizationID');
    }
}