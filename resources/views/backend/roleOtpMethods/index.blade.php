@extends('backend.layouts.app')
@section('title')
    {{ __('roles') }}
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title line-height-36">{{ __('roles_list') }}</h3>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">{{ __('name') }}</th>
                                    <th>{{ __('OTP Methods') }}</th>
                                    @if (auth()->user()->can('role.edit'))
                                        <th width="10%">{{ __('action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $role)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ ucwords($role->name) }}</td>
                                        <td>
                                            @foreach ($role->activeOtpMethods as $item)
                                                <span class="badge badge-primary permission">{{ $item->name }}</span>
                                            @endforeach
                                        </td>
                                        @if (auth()->user()->can('role.edit'))
                                            <td>
                                                <a href="{{ route('roles.otp-methods.edit', $role->id) }}"
                                                    class="btn bg-info"><i class="fas fa-edit"></i></a>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <x-admin.not-found word="roles" route="role.index" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection