<?php

namespace Cysha\Casino\Holdem\Tests\Game;

use Cysha\Casino\Exceptions\GameException;
use Cysha\Casino\Game\Chips;
use Cysha\Casino\Game\Client;
use Cysha\Casino\Game\PlayerCollection;
use Cysha\Casino\Game\TableCollection;
use Cysha\Casino\Holdem\Game\CashGame;
use Cysha\Casino\Holdem\Game\Dealer;
use Cysha\Casino\Holdem\Game\Parameters\CashGameParameters;
use Cysha\Casino\Holdem\Game\Player;
use Cysha\Casino\Holdem\Game\Table;
use Ramsey\Uuid\Uuid;
use TypeError;

class CashGameTest extends BaseGameTestCase
{
    /** @test */
    public function a_cash_game_can_be_setup()
    {
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, Chips::fromAmount(500));

        $game = CashGame::setUp($id, $name, $gameRules);
        $this->assertInstanceOf(CashGame::class, $game);
    }

    /**
     * @expectedException TypeError
     * @test
     */
    public function an_exception_is_thrown_when_id_is_not_valid()
    {
        $id = 'abc';
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, Chips::fromAmount(500));

        $game = CashGame::setUp($id, $name, $gameRules);
    }

    /** @test */
    public function i_can_see_the_id_of_the_game()
    {
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, Chips::fromAmount(500));

        $game = CashGame::setUp($id, $name, $gameRules);
        $this->assertEquals($id, $game->id());
    }

    /** @test */
    public function i_can_see_the_name_of_the_game()
    {
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, Chips::fromAmount(500));

        $game = CashGame::setUp($id, $name, $gameRules);
        $this->assertEquals($name, $game->name());
    }

    /** @test */
    public function the_game_should_be_setup_with_no_players_initialy()
    {
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, Chips::fromAmount(500));

        $game = CashGame::setUp($id, $name, $gameRules);
        $this->assertEquals(PlayerCollection::make(), $game->players());
        $this->assertEquals(0, $game->players()->count());
    }

    /** @test */
    public function a_client_can_register_to_a_game()
    {
        $gameId = Uuid::uuid4();
        $id = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $minimumBuyIn = Chips::fromAmount(500);
        $playerName = 'xLink';
        $xLink = Client::register(Uuid::uuid4(), $playerName, $minimumBuyIn);

        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, $minimumBuyIn);

        $game = CashGame::setUp($id, $name, $gameRules);
        $game->registerPlayer($xLink);

        /** @var Client $firstPlayer */
        $firstPlayer = $game->players()->first();

        $this->assertEquals(1, $game->players()->count());
        $this->assertEquals($playerName, $firstPlayer->name());
    }

    /** @test */
    public function multiple_clients_can_register_to_a_game()
    {
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $minimumBuyIn = Chips::fromAmount(500);
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, $minimumBuyIn);

        $game = CashGame::setUp($id, $name, $gameRules);

        $xLink = Client::register(Uuid::uuid4(), 'xLink', Chips::fromAmount(1000));
        $Jebus = Client::register(Uuid::uuid4(), 'Jebus', Chips::fromAmount(1000));

        $game->registerPlayer($xLink);
        $game->registerPlayer($Jebus);

        $this->assertEquals(Player::fromClient($xLink, $minimumBuyIn), $game->players()->get(0));
        $this->assertEquals(Player::fromClient($Jebus, $minimumBuyIn), $game->players()->get(1));
        $this->assertEquals(2, $game->players()->count());
    }

    /**
     * @expectedException \Cysha\Casino\Exceptions\GameException
     * @test
     */
    public function client_cannot_register_to_same_game_twice()
    {
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, Chips::fromAmount(500));

        $game = CashGame::setUp($id, $name, $gameRules);

        $xLink = Client::register(Uuid::uuid4(), 'xLink', Chips::fromAmount(1000));
        $Jebus = Client::register(Uuid::uuid4(), 'Jebus', Chips::fromAmount(1000));

        $game->registerPlayer($xLink);
        $game->registerPlayer($Jebus);
        $game->registerPlayer($xLink);
    }

    /** @test */
    public function a_player_can_buy_into_a_game_with_the_minimum_buy_in()
    {
        $client = Client::register(Uuid::uuid4(), 'Bob', Chips::fromAmount(1000));
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $minimumBuyIn = Chips::fromAmount(500);
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, $minimumBuyIn);

        $game = CashGame::setUp($id, $name, $gameRules);

        $game->registerPlayer($client, $minimumBuyIn);

        /** @var Player $player */
        $player = $game->players()->first();

        $this->assertInstanceOf(Player::class, $player);
        $this->assertEquals(500, $player->wallet()->amount());
        $this->assertEquals(500, $player->chipStack()->amount());
    }

    /**
     * @expectedException \Cysha\Casino\Exceptions\GameException
     * @test
     */
    public function test_an_exception_is_thrown_if_a_player_has_insufficient_funds_to_buy_in()
    {
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, Chips::fromAmount(500));

        $game = CashGame::setUp($id, $name, $gameRules);
        $player = Client::register(Uuid::uuid4(), 'xLink', Chips::fromAmount(0));

        $game->registerPlayer($player);
    }

    /** @test */
    public function can_create_game_with_a_table()
    {
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, Chips::fromAmount(500));

        $game = CashGame::setUp($id, $name, $gameRules);

        $xLink = Client::register(Uuid::uuid4(), 'xLink', Chips::fromAmount(5500));
        $jesus = Client::register(Uuid::uuid4(), 'jesus', Chips::fromAmount(5500));

        $game->registerPlayer($xLink, Chips::fromAmount(5000));
        $game->registerPlayer($jesus, Chips::fromAmount(5000));

        $game->assignPlayersToTables();

        /** @var Table $firstTable */
        $firstTable = $game->tables()->first();

        $this->assertCount(1, $game->tables());
        $this->assertInstanceOf(TableCollection::class, $game->tables());
        $this->assertInstanceOf(Table::class, $firstTable);
        $this->assertInstanceOf(Dealer::class, $firstTable->dealer());

        $actualPlayer = $firstTable->players()->first(function (Player $player) use ($xLink) {
            return $player->name() === $xLink->name();
        });

        $this->assertEquals($xLink->name(), $actualPlayer->name());
        $this->assertEquals($xLink->wallet(), $actualPlayer->wallet());

        $actualPlayer = $firstTable->players()->first(function (Player $player) use ($jesus) {
            return $player->name() === $jesus->name();
        });

        $this->assertEquals($jesus->name(), $actualPlayer->name());
        $this->assertEquals($jesus->wallet(), $actualPlayer->wallet());
    }

    /** @test */
    public function can_remove_player_from_game()
    {
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $minimumBuyIn = Chips::fromAmount(500);
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, $minimumBuyIn);

        $game = CashGame::setUp($id, $name, $gameRules);

        $xLink = Client::register(Uuid::uuid4(), 'xLink', Chips::fromAmount(1000));
        $Jebus = Client::register(Uuid::uuid4(), 'Jebus', Chips::fromAmount(1000));

        $game->registerPlayer($xLink);
        $game->registerPlayer($Jebus);

        $this->assertEquals(Player::fromClient($xLink, $minimumBuyIn), $game->players()->get(0));
        $this->assertEquals(Player::fromClient($Jebus, $minimumBuyIn), $game->players()->get(1));
        $this->assertEquals(2, $game->players()->count());

        $game->removePlayer($xLink);
        $this->assertEquals(1, $game->players()->count());
    }

    /**
     * @expectedException Cysha\Casino\Exceptions\GameException
     * @test
     */
    public function ensures_that_players_cant_be_removed_if_they_dont_exist_on_game()
    {
        $id = Uuid::uuid4();
        $gameId = Uuid::uuid4();
        $name = 'Demo Cash Game';
        $minimumBuyIn = Chips::fromAmount(500);
        $gameRules = new CashGameParameters($gameId, Chips::fromAmount(50), null, 9, $minimumBuyIn);

        $game = CashGame::setUp($id, $name, $gameRules);

        $xLink = Client::register(Uuid::uuid4(), 'xLink', Chips::fromAmount(1000));
        $Jebus = Client::register(Uuid::uuid4(), 'Jebus', Chips::fromAmount(1000));
        $melk = Client::register(Uuid::uuid4(), 'melk', Chips::fromAmount(1000));

        $game->registerPlayer($xLink);
        $game->registerPlayer($Jebus);

        $this->assertEquals(Player::fromClient($xLink, $minimumBuyIn), $game->players()->get(0));
        $this->assertEquals(Player::fromClient($Jebus, $minimumBuyIn), $game->players()->get(1));
        $this->assertEquals(2, $game->players()->count());

        $game->removePlayer($melk);
    }
}
