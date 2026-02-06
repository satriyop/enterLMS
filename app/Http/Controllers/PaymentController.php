<?php

namespace App\Http\Controllers;

use App\Domain\Payment\Services\PaymentService;
use App\Models\Course;
use App\Models\LearningPath;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * List user's payments.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $payments = Payment::query()
            ->forUser($user)
            ->with('payable')
            ->orderByDesc('created_at')
            ->paginate(10);

        return Inertia::render('payments/Index', [
            'payments' => $payments->through(fn (Payment $payment) => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'payable_type' => class_basename($payment->payable_type),
                'payable_title' => $this->getPayableTitle($payment),
                'amount' => $payment->amount,
                'formatted_amount' => $payment->getFormattedAmount(),
                'currency' => $payment->currency,
                'status' => $payment->status,
                'gateway' => $payment->gateway,
                'payment_url' => $payment->payment_url,
                'paid_at' => $payment->paid_at?->toISOString(),
                'expires_at' => $payment->expires_at?->toISOString(),
                'created_at' => $payment->created_at->toISOString(),
            ]),
        ]);
    }

    /**
     * Show payment details.
     */
    public function show(Request $request, Payment $payment): InertiaResponse
    {
        Gate::authorize('view', $payment);

        $payment->load('payable', 'enrollment');

        return Inertia::render('payments/Show', [
            'payment' => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'payable_type' => class_basename($payment->payable_type),
                'payable_title' => $this->getPayableTitle($payment),
                'payable_id' => $payment->payable_id,
                'amount' => $payment->amount,
                'formatted_amount' => $payment->getFormattedAmount(),
                'currency' => $payment->currency,
                'status' => $payment->status,
                'gateway' => $payment->gateway,
                'gateway_transaction_id' => $payment->gateway_transaction_id,
                'payment_url' => $payment->payment_url,
                'paid_at' => $payment->paid_at?->toISOString(),
                'expires_at' => $payment->expires_at?->toISOString(),
                'created_at' => $payment->created_at->toISOString(),
                'enrollment_id' => $payment->enrollment_id,
                'is_pending' => $payment->isPending(),
                'is_processing' => $payment->isProcessing(),
                'is_paid' => $payment->isPaid(),
                'can_retry' => $payment->isFailed() || $payment->isExpired(),
            ],
        ]);
    }

    /**
     * Create payment for a course and redirect to payment page.
     */
    public function createForCourse(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        try {
            // Check for existing pending payment
            $existingPayment = $this->paymentService->getPendingPayment($user, $course);
            if ($existingPayment) {
                return redirect()
                    ->route('payments.show', $existingPayment)
                    ->with('info', 'Anda memiliki pembayaran yang sedang menunggu.');
            }

            // Create new payment
            $payment = $this->paymentService->createCoursePayment($user, $course);

            return redirect()
                ->route('payments.show', $payment)
                ->with('success', 'Pembayaran berhasil dibuat. Silakan selesaikan pembayaran.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a pending payment.
     */
    public function cancel(Request $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('cancel', $payment);

        if (! $payment->isPending() && ! $payment->isProcessing()) {
            return back()->with('error', 'Pembayaran tidak dapat dibatalkan.');
        }

        $this->paymentService->cancelPayment($payment, 'Dibatalkan oleh pengguna');

        return redirect()
            ->route('payments.index')
            ->with('success', 'Pembayaran berhasil dibatalkan.');
    }

    /**
     * Get the title of the payable entity.
     */
    protected function getPayableTitle(Payment $payment): string
    {
        $payable = $payment->payable;

        if ($payable instanceof Course) {
            return $payable->title;
        }

        if ($payable instanceof LearningPath) {
            return $payable->title;
        }

        return 'Item tidak ditemukan';
    }
}
