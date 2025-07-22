<?php

namespace App\Models\ApplicantAttachments;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class AttachmentsModel extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tbl_application_attachments';

    protected $fillable = [
        'id',
        'application_id',
        'folder_id',
        'file_id',
        'file_name',
        'file_url',
        'updated_at',
        'created_at',
    ];
    
}
