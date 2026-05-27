<?php

namespace App\Http\Controllers;

use App\Enums\PetStage;
use App\Http\Requests\CreatePetRequest;
use App\Http\Resources\PetActionResource;
use App\Http\Resources\PetResource;
use App\Models\Pet;
use App\Models\PetAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PetController extends Controller
{
    /**
     * Get the current user's alive pet (with calculated stats), or null.
     */
    public function show(Request $request): JsonResponse
    {
        $pet = $this->getActivePet($request);

        if (!$pet) {
            return response()->json([
                'pet' => null,
                'message' => 'You do not have an active pet.',
            ]);
        }

        $pet->calculateCurrentStats();

        return response()->json([
            'pet' => new PetResource($pet),
        ]);
    }

    /**
     * Create a new pet for the user.
     */
    public function store(CreatePetRequest $request): JsonResponse
    {
        // Check if user already has an alive pet
        $existingPet = $this->getActivePet($request);
        if ($existingPet) {
            return response()->json([
                'message' => 'You already have an active pet. Abandon it first to create a new one.',
            ], 409);
        }

        $now = Carbon::now();

        $pet = Pet::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'stage' => PetStage::Egg,
            'hunger' => 50,
            'happiness' => 50,
            'energy' => 50,
            'cleanliness' => 50,
            'health' => 100,
            'weight' => 5,
            'age_minutes' => 0,
            'is_alive' => true,
            'is_sleeping' => false,
            'born_at' => $now,
            'last_interaction_at' => $now,
            'last_calculated_at' => $now,
        ]);

        return response()->json([
            'message' => 'Pet created successfully!',
            'pet' => new PetResource($pet),
        ], 201);
    }

    /**
     * Feed the pet.
     */
    public function feed(Request $request): JsonResponse
    {
        $pet = $this->getActivePetOrFail($request);
        if ($pet instanceof JsonResponse) {
            return $pet;
        }

        $pet->calculateCurrentStats();

        if (!$pet->is_alive) {
            return $this->petDeadResponse();
        }

        $oldHunger = $pet->hunger;
        $oldWeight = $pet->weight;

        $pet->hunger = min(100, $pet->hunger + 20);
        $pet->weight = $pet->weight + 3;
        $pet->last_interaction_at = Carbon::now();
        $pet->save();

        $this->logAction($pet, 'feed', [
            'hunger' => ['from' => $oldHunger, 'to' => $pet->hunger],
            'weight' => ['from' => $oldWeight, 'to' => $pet->weight],
        ]);

        return response()->json([
            'message' => 'Pet has been fed!',
            'pet' => new PetResource($pet),
        ]);
    }

    /**
     * Play with the pet.
     */
    public function play(Request $request): JsonResponse
    {
        $pet = $this->getActivePetOrFail($request);
        if ($pet instanceof JsonResponse) {
            return $pet;
        }

        $pet->calculateCurrentStats();

        if (!$pet->is_alive) {
            return $this->petDeadResponse();
        }

        $oldHappiness = $pet->happiness;
        $oldEnergy = $pet->energy;
        $oldHunger = $pet->hunger;

        $pet->happiness = min(100, $pet->happiness + 15);
        $pet->energy = max(0, $pet->energy - 10);
        $pet->hunger = max(0, $pet->hunger - 5);
        $pet->last_interaction_at = Carbon::now();
        $pet->save();

        $this->logAction($pet, 'play', [
            'happiness' => ['from' => $oldHappiness, 'to' => $pet->happiness],
            'energy' => ['from' => $oldEnergy, 'to' => $pet->energy],
            'hunger' => ['from' => $oldHunger, 'to' => $pet->hunger],
        ]);

        return response()->json([
            'message' => 'You played with your pet!',
            'pet' => new PetResource($pet),
        ]);
    }

    /**
     * Clean the pet.
     */
    public function clean(Request $request): JsonResponse
    {
        $pet = $this->getActivePetOrFail($request);
        if ($pet instanceof JsonResponse) {
            return $pet;
        }

        $pet->calculateCurrentStats();

        if (!$pet->is_alive) {
            return $this->petDeadResponse();
        }

        $oldCleanliness = $pet->cleanliness;

        $pet->cleanliness = min(100, $pet->cleanliness + 30);
        $pet->last_interaction_at = Carbon::now();
        $pet->save();

        $this->logAction($pet, 'clean', [
            'cleanliness' => ['from' => $oldCleanliness, 'to' => $pet->cleanliness],
        ]);

        return response()->json([
            'message' => 'Pet has been cleaned!',
            'pet' => new PetResource($pet),
        ]);
    }

    /**
     * Heal the pet.
     */
    public function heal(Request $request): JsonResponse
    {
        $pet = $this->getActivePetOrFail($request);
        if ($pet instanceof JsonResponse) {
            return $pet;
        }

        $pet->calculateCurrentStats();

        if (!$pet->is_alive) {
            return $this->petDeadResponse();
        }

        if ($pet->health >= 80) {
            return response()->json([
                'message' => 'Your pet is healthy enough and does not need healing.',
                'pet' => new PetResource($pet),
            ], 422);
        }

        $oldHealth = $pet->health;

        $pet->health = min(100, $pet->health + 25);
        $pet->last_interaction_at = Carbon::now();
        $pet->save();

        $this->logAction($pet, 'heal', [
            'health' => ['from' => $oldHealth, 'to' => $pet->health],
        ]);

        return response()->json([
            'message' => 'Pet has been healed!',
            'pet' => new PetResource($pet),
        ]);
    }

    /**
     * Put the pet to sleep.
     */
    public function sleep(Request $request): JsonResponse
    {
        $pet = $this->getActivePetOrFail($request);
        if ($pet instanceof JsonResponse) {
            return $pet;
        }

        $pet->calculateCurrentStats();

        if (!$pet->is_alive) {
            return $this->petDeadResponse();
        }

        if ($pet->is_sleeping) {
            return response()->json([
                'message' => 'Your pet is already sleeping.',
                'pet' => new PetResource($pet),
            ], 422);
        }

        $pet->is_sleeping = true;
        $pet->last_interaction_at = Carbon::now();
        $pet->save();

        $this->logAction($pet, 'sleep', [
            'is_sleeping' => ['from' => false, 'to' => true],
        ]);

        return response()->json([
            'message' => 'Pet is now sleeping. Zzz...',
            'pet' => new PetResource($pet),
        ]);
    }

    /**
     * Wake the pet up.
     */
    public function wake(Request $request): JsonResponse
    {
        $pet = $this->getActivePetOrFail($request);
        if ($pet instanceof JsonResponse) {
            return $pet;
        }

        $pet->calculateCurrentStats();

        if (!$pet->is_alive) {
            return $this->petDeadResponse();
        }

        if (!$pet->is_sleeping) {
            return response()->json([
                'message' => 'Your pet is already awake.',
                'pet' => new PetResource($pet),
            ], 422);
        }

        $pet->is_sleeping = false;
        $pet->last_interaction_at = Carbon::now();
        $pet->save();

        $this->logAction($pet, 'wake', [
            'is_sleeping' => ['from' => true, 'to' => false],
        ]);

        return response()->json([
            'message' => 'Pet is now awake!',
            'pet' => new PetResource($pet),
        ]);
    }

    /**
     * Discipline the pet.
     */
    public function discipline(Request $request): JsonResponse
    {
        $pet = $this->getActivePetOrFail($request);
        if ($pet instanceof JsonResponse) {
            return $pet;
        }

        $pet->calculateCurrentStats();

        if (!$pet->is_alive) {
            return $this->petDeadResponse();
        }

        $oldHappiness = $pet->happiness;

        $pet->happiness = max(0, $pet->happiness - 10);
        $pet->last_interaction_at = Carbon::now();
        $pet->save();

        $this->logAction($pet, 'discipline', [
            'happiness' => ['from' => $oldHappiness, 'to' => $pet->happiness],
        ]);

        return response()->json([
            'message' => 'Pet has been disciplined.',
            'pet' => new PetResource($pet),
        ]);
    }

    /**
     * Abandon (soft delete) the pet.
     */
    public function destroy(Request $request): JsonResponse
    {
        $pet = $this->getActivePetOrFail($request);
        if ($pet instanceof JsonResponse) {
            return $pet;
        }

        $pet->is_alive = false;
        $pet->save();

        return response()->json([
            'message' => 'Pet has been abandoned. Goodbye, ' . $pet->name . '...',
        ]);
    }

    /**
     * Get action history for the current pet.
     */
    public function history(Request $request): JsonResponse
    {
        $pet = $this->getActivePet($request);

        if (!$pet) {
            return response()->json([
                'message' => 'You do not have an active pet.',
                'actions' => [],
            ]);
        }

        $actions = $pet->actions()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'actions' => PetActionResource::collection($actions),
        ]);
    }

    /**
     * Get the active pet for the current user.
     */
    private function getActivePet(Request $request): ?Pet
    {
        return Pet::where('user_id', $request->user()->id)
            ->where('is_alive', true)
            ->latest()
            ->first();
    }

    /**
     * Get the active pet or return an error response.
     */
    private function getActivePetOrFail(Request $request): Pet|JsonResponse
    {
        $pet = $this->getActivePet($request);

        if (!$pet) {
            return response()->json([
                'message' => 'You do not have an active pet. Create one first.',
            ], 404);
        }

        return $pet;
    }

    /**
     * Return a standard "pet is dead" error response.
     */
    private function petDeadResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Your pet has died. Create a new one to play again.',
        ], 422);
    }

    /**
     * Log an action for the pet.
     */
    private function logAction(Pet $pet, string $action, array $statChanges): void
    {
        PetAction::create([
            'pet_id' => $pet->id,
            'action' => $action,
            'stat_changes' => $statChanges,
        ]);
    }
}
