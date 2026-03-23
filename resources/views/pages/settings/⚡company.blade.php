<?php

use Livewire\Component;
use App\Utilities\CONST_DEF;
use App\Models\Entity;

new class extends Component
{
    //
    public String $name = "";
    public String $full_name = "";
    public String $id = "";//entity_id
    public String $phone1 = "";
    public String $email1 = "";
    public String $address = "";
    public String $region = "";


    public function mount(){

        /**
        * @var Entity $entity_info
        */
        
        //$entity_id = session(CONST_DEF::$SESSION_ENTITY_ID);
        $id = request()->session()->get(CONST_DEF::$SESSION_ENTITY_ID);
        $entity_info = request()->session()->get(CONST_DEF::$SESSION_ENTITY_DATA);

        $this->id = $id;

        if(!$entity_info){
            $entity_info = Entity::where("id",$this->id)->first();  
        }

        $this->entity_id = $id;
        $this->phone1 = $entity_info->phone1 ==null ? "" : $entity_info->phone1;
        $this->email1 = is_null($entity_info->email1) ? "" : $entity_info->email1;
        $this->address = is_null($entity_info->address) ? "" : $entity_info->address;
        $this->name = is_null($entity_info->name) ? "" : $entity_info->name;
        $this->full_name = is_null($entity_info->full_name) ? "" : $entity_info->full_name;
        $this->region = is_null($entity_info->region) ? "" : $entity_info->region;
        
    }

    public function updateCompanyInfo(){
        Log::info("Update CompanyInfo : ".$this->phone1);
        //
        Entity::where("id",$this->id)->update([
            "name" => $this->name,
            "full_name" => $this->full_name,
            "phone1" => $this->phone1,
            "email1" => $this->email1,
            "address" => $this->address,
            "region" => $this->region
        ]);
        
        //
        $newEntityData = Entity::where("id",$this->id)->first();
        request()->session()->put(CONST_DEF::$SESSION_ENTITY_DATA,$newEntityData);

        $this->dispatch('company-updated', name: $this->name);
    }



};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Company settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Company')" :subheading="__('Update the company settings for your account')">
        <form wire:submit="updateCompanyInfo" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />
            <flux:input wire:model="full_name" :label="__('Full-Name')" type="text" required autofocus autocomplete="full_name" />
            <flux:input wire:model="phone1" :label="__('Phone')" type="text" required autofocus autocomplete="phone1" />
            <flux:input wire:model="email1" :label="__('Email')" type="text" required autofocus autocomplete="email1" />
            <flux:input wire:model="region" :label="__('Region')" type="text" required autofocus autocomplete="region" />
            <flux:textarea wire:model="address" :label="__('Adress')" type="textarea" required autofocus autocomplete="address" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="company-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>

        </form>
    </x-pages::settings.layout>
</section>
