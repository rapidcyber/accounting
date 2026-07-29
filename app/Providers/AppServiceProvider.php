<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use App\Models\Expense;
use App\Observers\ExpenseObserver;
use App\Models\Budget;
use App\Observers\BudgetObserver;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use App\Filament\Resources\ExpenseResource;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        Expense::observe(ExpenseObserver::class);
        Budget::observe(BudgetObserver::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => Blade::render(<<<'HTML'
                <style>
                    .pagination-loading {
                        position: fixed;
                        top: 16px;
                        left: 50%;
                        transform: translateX(-50%);
                        z-index: 9999;
                        background: rgb(255, 255, 255);
                        box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
                        border-radius: 0.5rem;
                        padding: 0.5rem 1rem;
                        display: none;
                        align-items: center;
                        gap: 0.5rem;
                        border: 1px solid rgb(229 231 235);
                    }
                    .dark .pagination-loading {
                        background: rgb(31 41 55);
                        border-color: rgb(55 65 81);
                    }
                    .pagination-loading.show {
                        display: flex;
                    }
                </style>
                <div id="pagination-loading" class="pagination-loading">
                    <svg class="w-5 h-5 animate-spin text-primary-600 dark:text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Loading...</span>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const loadingIndicator = document.getElementById('pagination-loading');
                        let hideTimeout;
                        
                        function hideLoading() {
                            clearTimeout(hideTimeout);
                            loadingIndicator.classList.remove('show');
                        }
                        
                        // Show loading indicator immediately when pagination buttons are clicked
                        document.addEventListener('click', function(e) {
                            const button = e.target.closest('button');
                            if (button && (
                                button.textContent.includes('Next') || 
                                button.textContent.includes('Previous') ||
                                button.classList.contains('fi-ta-pagination-link') ||
                                button.closest('.fi-ta-pagination')
                            )) {
                                loadingIndicator.classList.add('show');
                                // Fallback: hide after 3 seconds if Livewire hook doesn't fire
                                hideTimeout = setTimeout(hideLoading, 3000);
                            }
                        });
                        
                        // Hide loading indicator when Livewire message is processed
                        document.addEventListener('livewire:init', function() {
                            Livewire.hook('message.processed', function() {
                                hideLoading();
                            });
                            
                            Livewire.hook('message.failed', function() {
                                hideLoading();
                            });
                            
                            Livewire.hook('response', function() {
                                hideLoading();
                            });
                        });
                    });
                </script>
            HTML),
        );
    }
}
