<?php

namespace App\Http\Resources\Registrar;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->whenLoaded('user');
        $hasAccount = $user !== null;

        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'full_name' => $this->full_name,
            'sex' => $this->sex?->value,
            'contact_number' => $this->contact_number,
            'year_level' => $this->year_level,
            'status' => $this->status,
            'program' => $this->whenLoaded('program', function () {
                return [
                    'id' => $this->program->id,
                    'code' => $this->program->code,
                    'name' => $this->program->name,
                    'department' => $this->whenLoaded('program.department', function () {
                        return [
                            'id' => $this->program->department->id,
                            'code' => $this->program->department->code,
                            'name' => $this->program->department->name,
                        ];
                    }),
                ];
            }),
            'account' => $hasAccount ? [
                'user_id' => $user->user_id,
                'status' => $user->status,
                'must_change_password' => $user->must_change_password,
            ] : null,
            'has_account' => $hasAccount,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}