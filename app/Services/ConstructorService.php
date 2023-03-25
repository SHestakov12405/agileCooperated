<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuForWeek;
use App\Services\TotalService;
use Illuminate\Support\Facades\Auth;
use App\QueryBuilders\MenuQueryBuilder;
use App\Services\Contracts\Constructor;
use App\QueryBuilders\UsersQueryBuilder;
use App\QueryBuilders\RecipesQueryBuilder;
use App\QueryBuilders\MenuWeekQueryBuilder;
use App\QueryBuilders\ProfilesQueryBuilder;
use App\QueryBuilders\CategoriesQueryBuilder;




class ConstructorService implements Constructor
{

    public function constructor()
    {
        Auth::attempt(['email' => 'email@mail.ru', 'password' => 'password']);

        $userProfile = new ProfilesQueryBuilder();
        $userProfile = $userProfile->getByUserIdFirst(\Auth::id());

        $caloricNorm = $userProfile->caloric_norm;

        $menu = new MenuQueryBuilder();

        $menu = $menu->getFromConstructor(
            $userProfile->caloric_norm,
            $userProfile->fats_min,
            $userProfile->proteins_min,
            $userProfile->carbohydrates_min,
            $userProfile->fats_max,
            $userProfile->proteins_max,
            $userProfile->carbohydrates_max
        );

        $idMenuForWeek = [];
        if ($menu->count() > 7 && $menu->count() < 15) {

            foreach ($menu as $key => $value) {
                if (count($idMenuForWeek) === 6) {
                    break;
                }
                $idMenuForWeek[] = $value->id;
            }

            $idMenuForWeek[] = $this->createMenuFromCaloricNorm($caloricNorm);


        }elseif($menu->count() > 14){
            foreach ($menu as $key => $value) {
                if (count($idMenuForWeek) === 7) {
                    break;
                }
                $idMenuForWeek[] = $value->id;
            }

        }elseif($menu->count() < 8) {
            for ($i=0; $i < 7; $i++) {
                $idMenuForWeek[] = $this->createMenuFromCaloricNorm($caloricNorm);
            }
        }


        if (count($idMenuForWeek) < 6) {
            dd('error');
        }

        $menuWeek = new MenuForWeek();

        $menuWeek->monday()->associate($idMenuForWeek[0]);
        $menuWeek->tuesday()->associate($idMenuForWeek[1]);
        $menuWeek->wednesday()->associate($idMenuForWeek[2]);
        $menuWeek->thursday()->associate($idMenuForWeek[3]);
        $menuWeek->friday()->associate($idMenuForWeek[4]);
        $menuWeek->saturday()->associate($idMenuForWeek[5]);
        $menuWeek->sunday()->associate($idMenuForWeek[6]);

        if ($menuWeek->save()) {
            $this->checkAndDropMenuWeek();
            $menuWeek->user()->sync(\Auth::id());

        }


        // $total = $totalService->getTotalForDay($item->id);
    }

    public function checkAndDropMenuWeek()
    {
        $user = new UsersQueryBuilder();
        $user = $user->getById(\Auth::id());

        if ($user->menuWeek()->pluck('id')->count()) {
            foreach ($user->menuWeek()->pluck('id') as $key => $value) {
                $menuWeekDelete = new MenuWeekQueryBuilder();
                $menuWeekDelete->deleteById($value);
            }
        }

    }

    public function getCategoryList()
    {
        $category = new CategoriesQueryBuilder();
        $category = $category->getAll();

        $categoryList = [];
        foreach ($category as $key => $value) {
            $categoryList[$value->title] = $value->id;
        }

        return $categoryList;
    }

    public function createMenuFromCaloricNorm(int $caloricNorm)
    {

        $categoryList = $this->getCategoryList();
        $normDay = [
            'breakfestCaloricNorm' => ['caloric' => (int)round($caloricNorm * 0.3), 'category' => $categoryList['Завтрак']],
            'lunchCaloricNorm' => ['caloric' => (int)round($caloricNorm * 0.35), 'category' =>  $categoryList['Обед']],
            'dinnerCaloricNorm' => ['caloric' => (int)round($caloricNorm * 0.25), 'category' =>  $categoryList['Ужин']],
            'firstSnackCaloricNorm' => ['caloric' => (int)round($caloricNorm * 0.05), 'category' =>  $categoryList['Перекус']],
            'secondSnackCaloricNorm' => ['caloric' => (int)round($caloricNorm * 0.05), 'category' =>  $categoryList['Перекус']],
        ];


        foreach ($normDay as $key => $value) {
            $recipe = new RecipesQueryBuilder();
            $recipeIdForCreateMenu[$key] = $recipe->getRecipeIdByCaloricNorm($value['caloric'], $value['category'])->id;
        }


        $createMenu = new Menu();

        $createMenu->breakfest()->associate($recipeIdForCreateMenu['breakfestCaloricNorm']);
        $createMenu->dinner()->associate($recipeIdForCreateMenu['lunchCaloricNorm']);
        $createMenu->lunch()->associate($recipeIdForCreateMenu['dinnerCaloricNorm']);
        $createMenu->firstSnack()->associate($recipeIdForCreateMenu['firstSnackCaloricNorm']);
        $createMenu->secondSnack()->associate($recipeIdForCreateMenu['secondSnackCaloricNorm']);


        if ($createMenu->save()) {
            $total = new TotalService();
            $total->getTotalMenuForDay($createMenu->id);
            return $createMenu->id;
        }
    }

}