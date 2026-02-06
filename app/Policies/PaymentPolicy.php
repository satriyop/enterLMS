<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own payments list
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): bool
    {
        // User can view their own payments, admin can view all
        return $user->id === $payment->user_id || $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isLearner();
    }

    /**
     * Determine whether the user can cancel the payment.
     */
    public function cancel(User $user, Payment $payment): bool
    {
        // Only owner can cancel their pending payment
        return $user->id === $payment->user_id && $payment->isPending();
    }

    /**
     * Determine whether the user can refund the payment.
     */
    public function refund(User $user, Payment $payment): bool
    {
        // Only admin can refund
        return $user->isLmsAdmin() && $payment->isPaid();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Payment $payment): bool
    {
        return false; // Payments are not directly updatable
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return false; // Payments should never be deleted
    }
}
