@extends('layouts.app')

@section('title', 'Offer Accepted')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-success text-white text-center">
                    <h3 class="mb-0">✅ Offer Accepted Successfully!</h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <div class="display-1 text-success">🎉</div>
                    </div>
                    
                    <h4>Dear {{ $application->applicant->full_name }},</h4>
                    
                    <p class="lead">Thank you for accepting our offer letter!</p>
                    
                    <div class="alert alert-info mt-4">
                        <strong>Next Steps:</strong>
                        <ul class="text-left mt-2">
                            <li>You will receive a confirmation letter within 3-5 working days</li>
                            <li>You will be assigned to a school based on your location preference</li>
                            <li>An orientation will be scheduled before you start</li>
                        </ul>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('applicant.dashboard') }}" class="btn btn-primary">
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection