<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Patient extends Model
{
    use HasFactory;

    private const COMMON_INFORMATION_ATTRIBUTES = [
        'phone',
        'birthdate',
        'gender',
        'place_of_birth',
        'height_m',
        'weight_kg',
        'is_pwd',
        'is_senior',
        'address',
    ];

    private const STUDENT_INFORMATION_ATTRIBUTES = [
        'student_no',
        'course_code',
        'course_name',
        'year_level',
        'section',
    ];

    private const FACULTY_INFORMATION_ATTRIBUTES = [
        'faculty_code',
    ];

    private array $pendingCommonInformationAttributes = [];

    private array $pendingStudentInformationAttributes = [];

    private array $pendingFacultyInformationAttributes = [];

    protected $fillable = [
        'user_id',
        'name',
        'email',

        'phone',
        'birthdate',
        'gender',
        'place_of_birth',
        'height_m',
        'weight_kg',
        'is_pwd',
        'is_senior',
        'address',

        'faculty_code',

        'classification',

        'student_no',
        'course_code',
        'course_name',
        'year_level',
        'section',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$value) {
                    return $value;
                }

                $formattedName = mb_convert_case(
                    mb_strtolower(trim($value), 'UTF-8'),
                    MB_CASE_TITLE,
                    'UTF-8'
                );

                $suffix = trim((string) ($this->user?->suffix_name ?? ''));

                if (
                    $suffix !== '' &&
                    !preg_match(
                        '/(?:^|\\s)' . preg_quote($suffix, '/') . '\\.?$/iu',
                        $formattedName
                    )
                ) {
                    $formattedName .= ' ' . $suffix;
                }

                return preg_replace_callback(
                    '/\b(ii|iii|iv|v|vi|vii|viii|ix|x)\.?$/i',
                    fn($matches) => strtoupper($matches[0]),
                    $formattedName
                ) ?? $formattedName;
            }
        );
    }


    private function makeRelatedAccessor(
        string $relationName,
        string $attribute,
        string $pendingProperty
    ) {
        return Attribute::make(
            get: function ($value) use (
                $relationName,
                $attribute,
                $pendingProperty
            ) {
                $pending = isset($this->{$pendingProperty})
                    && is_array($this->{$pendingProperty})
                        ? $this->{$pendingProperty}
                        : [];

                if (array_key_exists($attribute, $pending)) {
                    return $pending[$attribute];
                }

                if (!$this->exists) {
                    return null;
                }

                $related = $this->relationLoaded($relationName)
                    ? $this->getRelation($relationName)
                    : $this->{$relationName}()->first();

                return $related?->getAttribute($attribute);
            }
        );
    }


    public function setAttribute($key, $value)
    {
        if (is_string($key)) {
            if (
                in_array(
                    $key,
                    self::COMMON_INFORMATION_ATTRIBUTES,
                    true
                )
            ) {
                $this->pendingCommonInformationAttributes[$key] = $value;

                return $this;
            }

            if (
                in_array(
                    $key,
                    self::STUDENT_INFORMATION_ATTRIBUTES,
                    true
                )
            ) {
                $this->pendingStudentInformationAttributes[$key] = $value;

                return $this;
            }

            if (
                in_array(
                    $key,
                    self::FACULTY_INFORMATION_ATTRIBUTES,
                    true
                )
            ) {
                $this->pendingFacultyInformationAttributes[$key] = $value;

                return $this;
            }
        }

        return parent::setAttribute($key, $value);
    }

    protected static function booted(): void
    {
        static::saved(function (Patient $patient): void {
            $patient->syncCommonInformationRecord();
            $patient->syncStudentInformationRecord();
            $patient->syncFacultyInformationRecord();
        });
    }

    private function syncCommonInformationRecord(): void
    {
        $attributes = $this->pendingCommonInformationAttributes;

        if (
            $attributes === [] &&
            $this->information()->exists()
        ) {
            return;
        }

        $this->information()->updateOrCreate(
            [],
            $attributes
        );

        $this->pendingCommonInformationAttributes = [];

        $this->unsetRelation('information');
    }

   
    private function syncStudentInformationRecord(): void
    {
        $attributes = $this->pendingStudentInformationAttributes;

        if ($attributes === []) {
            return;
        }

        $this->studentInformation()->updateOrCreate(
            [],
            $attributes
        );

        $this->pendingStudentInformationAttributes = [];

        $this->unsetRelation('studentInformation');
    }


    private function syncFacultyInformationRecord(): void
    {
        $attributes = $this->pendingFacultyInformationAttributes;

        if ($attributes === []) {
            return;
        }

        $this->facultyInformation()->updateOrCreate(
            [],
            $attributes
        );

        $this->pendingFacultyInformationAttributes = [];

        $this->unsetRelation('facultyInformation');
    }


    protected function phone(): Attribute
    {
        return $this->makeRelatedAccessor(
            'information',
            'phone',
            'pendingCommonInformationAttributes'
        );
    }

    protected function birthdate(): Attribute
    {
        return $this->makeRelatedAccessor(
            'information',
            'birthdate',
            'pendingCommonInformationAttributes'
        );
    }

    protected function gender(): Attribute
    {
        return $this->makeRelatedAccessor(
            'information',
            'gender',
            'pendingCommonInformationAttributes'
        );
    }

    protected function placeOfBirth(): Attribute
    {
        return $this->makeRelatedAccessor(
            'information',
            'place_of_birth',
            'pendingCommonInformationAttributes'
        );
    }

    protected function heightM(): Attribute
    {
        return $this->makeRelatedAccessor(
            'information',
            'height_m',
            'pendingCommonInformationAttributes'
        );
    }

    protected function weightKg(): Attribute
    {
        return $this->makeRelatedAccessor(
            'information',
            'weight_kg',
            'pendingCommonInformationAttributes'
        );
    }

    protected function isPwd(): Attribute
    {
        return $this->makeRelatedAccessor(
            'information',
            'is_pwd',
            'pendingCommonInformationAttributes'
        );
    }

    protected function isSenior(): Attribute
    {
        return $this->makeRelatedAccessor(
            'information',
            'is_senior',
            'pendingCommonInformationAttributes'
        );
    }

    protected function address(): Attribute
    {
        return $this->makeRelatedAccessor(
            'information',
            'address',
            'pendingCommonInformationAttributes'
        );
    }

 

    protected function studentNo(): Attribute
    {
        return $this->makeRelatedAccessor(
            'studentInformation',
            'student_no',
            'pendingStudentInformationAttributes'
        );
    }

    protected function courseCode(): Attribute
    {
        return $this->makeRelatedAccessor(
            'studentInformation',
            'course_code',
            'pendingStudentInformationAttributes'
        );
    }

    protected function courseName(): Attribute
    {
        return $this->makeRelatedAccessor(
            'studentInformation',
            'course_name',
            'pendingStudentInformationAttributes'
        );
    }

    protected function yearLevel(): Attribute
    {
        return $this->makeRelatedAccessor(
            'studentInformation',
            'year_level',
            'pendingStudentInformationAttributes'
        );
    }

    protected function section(): Attribute
    {
        return $this->makeRelatedAccessor(
            'studentInformation',
            'section',
            'pendingStudentInformationAttributes'
        );
    }



    protected function facultyCode(): Attribute
    {
        return $this->makeRelatedAccessor(
            'facultyInformation',
            'faculty_code',
            'pendingFacultyInformationAttributes'
        );
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dentalHistory()
    {
        return $this->hasOne(\App\Models\DentalHistory::class);
    }

    public function dentalHistoryDates()
    {
        return $this->hasOne(\App\Models\DentalHistoryConditionDate::class);
    }

    public function dentalHistoryConcerns()
    {
        return $this->hasOne(\App\Models\DentalHistoryConcern::class);
    }

    public function dentalHistoryAnswers()
    {
        return $this->hasMany(\App\Models\DentalHistoryAnswer::class);
    }

    public function medicalHistory()
    {
        return $this->hasOne(\App\Models\MedicalHistory::class);
    }

    public function appointments()
    {
        return $this->hasMany(
            \App\Models\Appointment::class,
            'patient_id'
        );
    }

    public function documentRequests()
    {
        return $this->hasMany(
            \App\Models\DocumentRequest::class
        );
    }

    public function teeth()
    {
        return $this->hasMany(Tooth::class);
    }

    public function odontogram()
    {
        return $this->hasOne(
            PatientOdontogram::class
        );
    }

    public function information()
    {
        return $this->hasOne(
            PatientInformation::class
        );
    }

    public function studentInformation()
    {
        return $this->hasOne(
            PatientStudentInformation::class
        );
    }

    public function facultyInformation()
    {
        return $this->hasOne(
            PatientFacultyInformation::class
        );
    }
}