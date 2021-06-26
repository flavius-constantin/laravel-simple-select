<div
    class="relative mt-1"
    x-data="SimpleSelect({
        dataSource: {{ is_array($options) ? json_encode($options) : json_encode([]) }},
        @if($attributes->has('wire:model'))
            selected: @entangle($attributes->wire('model')),
        @else
            selected: '',
        @endif
        valueField: '{{ $valueField }}',
        textField: '{{ $textField }}',
        value: @if(is_array($value)) {{ json_encode($value) }} @else '{{ $value }}' @endif,
        name: '{{ $name }}',
        id: '{{ $id }}',
        placeholder: '{{ $placeholder }}',
        searchInputPlaceholder: '{{ $searchInputPlaceholder }}',
        noOptions: '{{ $noOptions }}',
        noResult: '{{ $noResult }}',
        multiple: {{ isset($attributes['multiple']) ? 'true' : 'false' }},
        maxSelection: '{{ $maxSelection }}',
        required: {{ isset($attributes['required']) ? 'true' : 'false' }},
        disabled: {{ isset($attributes['disabled']) ? 'true' : 'false' }},
        searchable: {{ $searchable ? 'true' : 'false' }},
        clearable: {{ $clearable ? 'true' : 'false' }},
        onSelect: '{{ $attributes['on-select'] ?? 'select' }}'
    })"
    x-init="init();"
    x-on:click.outside="closeSelect()"
    x-on:keydown.escape="closeSelect()"
    :wire:key="`${id}${generateID()}`"
>
    <div
        x-ref="simpleSelectButton"
        x-on:click="toggleSelect()"
        x-on:keyup.enter="toggleSelect()"
        tabindex="0"
        x-bind:class="{
            'rounded-md': !open,
            'rounded-t-md': open,
            'bg-gray-200 cursor-default': disabled
        }"
        {{ $attributes->class('block w-full border border-gray-300 rounded-md shadow-sm focus:ring-0 focus:ring-gray-400 focus:border-gray-400 sm:text-sm sm:leading-5')->only('class'); }}
    > 
        <div x-cloak x-show="!selected || selected.length === 0" class="flex flex-wrap">
            <div class="text-gray-800 rounded-sm w-full truncate px-2 py-0.5 my-0.5 flex flex-row items-center">
                <div class="w-full px-2 truncate" x-text="placeholder">&nbsp;</div>
                <div x-show="!disabled" x-bind:class="{ 'cursor-pointer': !disabled }" class="h-4" x-on:click.prevent.stop="toggleSelect()">
                    @include('simple-select::components.caret-icons')
                </div>
            </div>
        </div>
        @isset($attributes['multiple'])            
            <div x-cloak x-show="selected && typeof selected === 'object' && selected.length > 0" class="flex flex-wrap space-x-1">
                <template x-for="(value, index) in selected" :key="index">
                    <div class="text-gray-800 rounded-full truncate bg-gray-300 px-2 py-0.5 my-0.5 flex flex-row items-center">
                        {{-- Invisible inputs for standard form submission values --}}
                        <input type="text" :name="`${name}[]`" x-model="value" style="display: none;" />
                        <div class="px-2 truncate" x-text="getTextFromSelectedValue(value)"></div>
                        <div
                            x-show="!disabled"
                            x-bind:class="{ 'cursor-pointer': !disabled }"
                            x-on:click.prevent.stop="deselectOption(index)"
                            x-on:keyup.enter="deselectOption(index)"
                            class="w-4"
                            tabindex="0"
                        >
                            @include('simple-select::components.deselect-icon')
                        </div>
                    </div>
                </template>
            </div>
        @else            
            <div x-cloak x-show="selected" class="flex flex-wrap"> 
                <div class="text-gray-800 rounded-sm w-full truncate px-2 py-0.5 my-0.5 flex flex-row items-center">
                    {{-- Invisible input for standard form submission of values --}}
                    <input type="text" :name="name" x-model="selected" :required="required" style="display: none;" />
                    <div class="w-full px-2 truncate" x-text="getTextFromSelectedValue(selected)"></div>
                    <div
                        x-show="!disabled && clearable"
                        x-bind:class="{ 'cursor-pointer': !disabled }"
                        x-on:click.prevent.stop="deselectOption()"
                        x-on:keyup.enter="deselectOption()"
                        class="h-4"
                        tabindex="0"
                    >
                        @include('simple-select::components.deselect-icon')                  
                    </div>                  
                    <div
                        x-show="!disabled && !clearable"
                        x-bind:class="{ 'cursor-pointer': !disabled }"
                        class="h-4"
                        tabindex="0"
                    >
                        @include('simple-select::components.caret-icons')                 
                    </div>
                </div>
            </div>
        @endisset
    </div>
    <div x-ref="simpleSelectOptionsContainer" x-bind:style="open ? 'height: ' + popperHeight : ''" class="absolute w-full">
        <div x-show="open">
            <input
                type="search"
                x-show="searchable"
                x-ref="simpleSelectOptionsSearch"
                x-model="search"
                x-on:click.prevent.stop="open=true"
                :placeholder="searchInputPlaceholder"
                class="block w-full p-2 bg-gray-100 border border-gray-300 shadow-md focus:border-gray-200 focus:ring-0 sm:text-sm sm:leading-5"
            />
            <ul                
                x-ref="simpleSelectOptionsList"
                class="absolute z-10 w-full py-1 overflow-auto text-base bg-white shadow-lg rounded-b-md max-h-60 ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
                tabindex="-1"
                role="listbox"
            >
                <div x-cloak x-show="Object.values(options).length == 0 && search.toString().trim() == ''" x-text="noOptions" class="px-2 py-2"></div>
                <div x-cloak x-show="Object.values(options).length == 0 && search.toString().trim() != ''" x-text="noResult" class="px-2 py-2"></div>
                <template x-for="(option, index) in Object.values(options)" :key="index">
                    <li               
                        :tabindex="index"             
                        class="relative py-2 pl-3 select-none pr-9 "
                        @isset($attributes['multiple'])
                            x-bind:class="{
                                'bg-gray-300 text-black hover:none': selected && selected.includes(getOptionValue(option, index)),
                                'text-gray-900 cursor-defaul hover:bg-gray-200 hover:cursor-pointer focus:bg-gray-200': !(selected && selected.includes(getOptionValue(option, index))),
                            }"
                        @else
                            x-bind:class="{
                                'bg-gray-300 text-black hover:none': selected == getOptionValue(option, index),
                                'text-gray-900 cursor-defaul hover:bg-gray-200 hover:cursor-pointer focus:bg-gray-200': !(selected == getOptionValue(option, index)),
                            }"
                        @endisset
                        x-on:click="selectOption(getOptionValue(option, index))"
                        x-on:keyup.enter="selectOption(getOptionValue(option, index))"
                    >
                        @isset($customOption)
                            {{ $customOption }}
                        @else
                            <span x-text="getOptionText(option, index)"></span>
                        @endisset
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>

@include('simple-select::components.script')