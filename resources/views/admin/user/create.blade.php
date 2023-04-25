@extends('admin.layouts.app')
@section('page-title', 'Add User')
@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.user.store') }}">
            @csrf

            <div class="row mb-3">
                <label for="firstname" class="col-md-4 col-form-label text-md-end">{{ __('First Name') }} <span class="text-danger">*</span></label>

                <div class="col-md-6">
                    <input id="firstname" type="text" class="form-control @error('firstname') is-invalid @enderror" name="firstname" value="{{ old('firstname') }}" autofocus>

                    @error('firstname')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="middlename" class="col-md-4 col-form-label text-md-end">{{ __('Middle Name') }}</label>

                <div class="col-md-6">
                    <input id="middlename" type="text" class="form-control @error('middlename') is-invalid @enderror" name="middlename" value="{{ old('middlename') }}" autofocus>

                    @error('middlename')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="lastname" class="col-md-4 col-form-label text-md-end">{{ __('Last Name') }} <span class="text-danger">*</span></label>

                <div class="col-md-6">
                    <input id="lastname" type="text" class="form-control @error('lastname') is-invalid @enderror" name="lastname" value="{{ old('lastname') }}" autofocus>

                    @error('lastname')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="suffix" class="col-md-4 col-form-label text-md-end">{{ __('Suffix') }}</label>

                <div class="col-md-6">
                    <input id="suffix" type="text" class="form-control @error('suffix') is-invalid @enderror" name="suffix" value="{{ old('suffix') }}" autofocus>

                    @error('suffix')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="position" class="col-md-4 col-form-label text-md-end">{{ __('Position') }} <span class="text-danger">*</span></label>

                <div class="col-md-6">
                    <select class="form-control selectpicker @error('position') is-invalid @enderror" id="position" name="position" data-live-search="true" data-width="100%" data-dropup-auto="false" data-size="5">
                        <option></option>
                        @foreach ($positions as $position)
                            <option style="width: 430px;" value="{{ $position->code }}" @if (old('position') == $position->code) selected @endif>{{ $position->position_name }}</option>
                        @endforeach
                    </select>
                    @error('position')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="office" class="col-md-4 col-form-label text-md-end">{{ __('Office') }} <span class="text-danger">*</span></label>

                <div class="col-md-6">
                    <select class="form-control selectpicker @error('office') is-invalid @enderror" id="office" name="office" data-live-search="true" data-width="100%" data-dropup-auto="false" data-size="5" autofocus>
                        <option></option>
                        @foreach ($offices as $office)
                            <option style="width: 430px;" value="{{ $office->code }}" @if (old('office') == $office->code) selected @endif>{{ $office->description }}</option>
                        @endforeach
                    </select>
                    @error('office')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="phone_number" class="col-md-4 col-form-label text-md-end">{{ __('Phone Number') }} <span class="text-danger">*</span></label>

                <div class="col-md-6">
                    <input id="phone_number" type="text" class="form-control @error('phone_number') is-invalid @enderror" name="phone_number" value="{{ old('phone_number') }}">

                    @error('phone_number')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Username') }} <span class="text-danger">*</span></label>

                <div class="col-md-6">
                    <input id="username" type="username" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" autocomplete="username">

                    @error('username')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }} <span class="text-danger">*</span></label>

                <div class="col-md-6">
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="new-password">

                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>

                <div class="col-md-6">
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" autocomplete="new-password">
                </div>
            </div>

            <div class="row mb-3 ">
                <label for="accountRole" class="col-md-4 col-form-label text-md-end">{{ __('Account Role') }} <span class="text-danger">*</span></label>

                <div class="col-md-6">
                    <select class="form-control selectpicker @error('accountRole') is-invalid @enderror" id="accountRole" name="accountRole" data-live-search="true" data-width="100%" data-dropup-auto="false" data-size="3" autofocus>
                        <option></option>
                        <option value="liaison" @if (old('accountRole')) selected @endif>Liaison</option>
                        <option value="checker" @if (old('accountRole')) selected @endif>Checker</option>
                    </select>
                    @error('accountRole')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3 d-none"  id="classIsSub">
                <label for="isSub" class="col-md-4 col-form-label text-md-end">{{ __('Is Sub') }} <span class="text-danger">*</span></label>
                <div class="col-md-6">
                    <select class="form-control selectpicker @error('isSub') is-invalid @enderror" id="isSub" name="isSub" data-live-search="true" data-width="100%" data-dropup-auto="false" data-size="3" autofocus>
                        <option value="0" @if (old('isSub')) selected @endif>No</option>
                        <option value="1" @if (old('isSub')) selected @endif>Yes</option>
                    </select>
                    @error('isSub')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-0  justify-content-end">
                <div class="col-md-6">
                    <a href="{{ route('admin.user.index') }}"><button type="button" class="btn btn-danger">Cancel</button></a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
@push('page-scripts')
<script>
$(document).ready(function(){
    $("#positionsDataTables").DataTable();
    $('#accountRole').change(function(){
        if($(this).val() == 'liaison'){
            $('#classIsSub').removeClass( "d-none" );
            $("#isSub").val("0").trigger("change");
        }else{
            $("#classIsSub").addClass( "d-none" );
            $("#isSub").val("1").trigger("change");
        }
    });
});
</script>
@endpush
