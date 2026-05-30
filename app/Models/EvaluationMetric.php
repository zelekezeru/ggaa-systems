<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationMetric extends Model
{
    protected $guarded = [];

    protected $casts = [
        'applies_to_roles' => 'array',
        'default_weight'   => 'decimal:2',
        'max_score'        => 'decimal:2',
        'is_system'        => 'boolean',
        'is_active'        => 'boolean',
    ];

    /**
     * Category catalogue — the weighted "types" of evaluation the user asked for.
     */
    public const CATEGORIES = [
        'task_performance'         => 'Task Performance',
        'team_project'             => 'Team Projects Performance',
        'daily_task'               => 'Daily Task Performance',
        'client_satisfaction'      => 'Client Satisfaction',
        'manager_review'           => 'Team Leader / Manager Review',
        'quality_compliance'       => 'Quality & Compliance',
        'professionalism'          => 'Professionalism & Ethics',
        'attendance'               => 'Attendance & Punctuality',
        'leadership'               => 'Leadership & Development',
        'custom'                   => 'Custom Metric',
    ];

    public function staffAssignments()
    {
        return $this->hasMany(StaffEvaluationMetric::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Does this metric apply to a user with the given role names?
     * A metric with no role restriction applies to everyone.
     */
    public function appliesToRoles(array $roleNames): bool
    {
        if (empty($this->applies_to_roles)) {
            return true;
        }

        return count(array_intersect($this->applies_to_roles, $roleNames)) > 0;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    protected $appends = ['category_label'];
}
