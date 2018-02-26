<?php

use Phalcon\Mvc\Controller;

class RecipesController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * Show list of recipes
	 */
	public function indexAction()
	{
		// get all recipes
		$recipes = Connection::query("SELECT * FROM _recetas ORDER BY inserted DESC");

		// send variables to the view
		$this->view->title = "List of recipes (".count($recipes).")";
		$this->view->buttons = [["caption"=>"New recipe", "href"=>"recipes/new", "icon"=>"plus"]];
		$this->view->recipes = $recipes;
	}

	/**
	 * Add a new recipe
	 */
	public function newAction()
	{
		// get meassures
		$meassures = Connection::query("SELECT meassure FROM _recetas_meassures ORDER BY meassure ASC");

		// send data to the view
		$this->view->type = "new";
		$this->view->title = "New recipe";
		$this->view->title = "";
		$this->view->image = "";
		$this->view->cookingTime = "";
		$this->view->ingredients = "";
		$this->view->difficulty = "";
		$this->view->directions = "";
		$this->view->meassures = $meassures;
		$this->view->difficulties = ['SENCILLA','MEDIANA','DIFICIL','EXPERTO'];
		$this->view->pick(['recipes/manage']);
	}

	/**
	 * Edit a recipe
	 */
	public function editAction()
	{
		// get the recipe
		$id = $this->request->get('id');
		$recipe = Connection::query("SELECT * FROM _recetas WHERE id = $id")[0];

		// get meassures
		$meassures = Connection::query("SELECT meassure FROM _recetas_meassures ORDER BY meassure ASC");

		// send variables to the view
		$this->view->type = $id;
		$this->view->title = "Edit recipe";
		$this->view->title = $recipe->title;
		$this->view->image = $recipe->image.".jpg";
		$this->view->cookingTime = $recipe->cooking_time;
		$this->view->ingredients = $recipe->ingredients;
		$this->view->difficulty = $recipe->difficulty;
		$this->view->directions = $recipe->directions;
		$this->view->meassures = $meassures;
		$this->view->difficulties = ['SENCILLA','MEDIANA','DIFICIL','EXPERTO'];
		$this->view->pick(['recipes/manage']);
	}

	/**
	 * Submit a new recipe
	 */
	public function recipeSubmitAction()
	{
		// get info from post
		$type = $this->request->get('type');
		$title = $this->request->get('title');
		$image = $this->request->get('image');
		$cookingTime = $this->request->get('cooking_time');
		$ingredients = $this->request->get('ingredients');
		$difficulty = $this->request->get('difficulty');
		$directions = $this->request->get('directions');

		// save the picture and create name
		$imgName = "";
		if( ! empty($_FILES["image"]["tmp_name"])) {
			$imgName = md5($title . rand());
			$picPath = $this->di->get('path')['root']."/public/recetas/$imgName.jpg";
			move_uploaded_file($_FILES["image"]["tmp_name"], $picPath);
		}

		// save new row to the database
		if($type == 'new') {
			Connection::query("
			INSERT INTO _recetas(title, image, cooking_time, ingredients, difficulty, directions)
			VALUES ('$title','$imgName','$cookingTime','$ingredients','$difficulty','$directions')");
		}

		// edit recipe from the database
		else {
			$img = empty($imgName) ? "" : "image='$imgName',";
			Connection::query("UPDATE _recetas SET $img title='$title',cooking_time='$cookingTime',ingredients='$ingredients',difficulty='$difficulty',directions='$directions' WHERE id='$type'");
		}

		// go back to the list of recipes
		$this->response->redirect('recipes');
	}
}
