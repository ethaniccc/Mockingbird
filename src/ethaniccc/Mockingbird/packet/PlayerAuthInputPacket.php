<?php

namespace ethaniccc\Mockingbird\packet;

use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket as PMPlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;

class PlayerAuthInputPacket extends PMPlayerAuthInputPacket{
    private $requestId = null;

    private $requestChangedSlots = null;

    private $transactionData = null;

    private $itemStackRequest = null;

    private $blockActions = null;

    public function getRequestId(): ?int{
        return $this->requestId;
    }

    public function getRequestChangedSlots(): ?array{
        return $this->requestChangedSlots;
    }

    public function getTransactionData(): ?UseItemTransactionData{
        return $this->transactionData;
    }

    public function getItemStackRequest(): ?ItemStackRequest{
        return $this->itemStackRequest;
    }

    public function getBlockActions(): ?array{
        return $this->blockActions;
    }

    public function hasInputFlag(int $inputFlag): bool{
        return ($this->getInputFlags() & (1 << $inputFlag)) !== 0;
    }

    protected function decodePayload(): void{
        parent::decodePayload();

        if($this->hasInputFlag(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION)){
            $in = $this;
            $this->requestId = $in->readGenericTypeNetworkId();
            $this->requestChangedSlots = [];
            if($this->requestId !== 0){
                for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
                    $this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
                }
            }

            $this->transactionData = new UseItemTransactionData;
            $this->transactionData->decode($in);
        }

        if($this->hasInputFlag(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST)){
            $this->itemStackRequest = ItemStackRequest::read($this);
        }

        if($this->hasInputFlag(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS)){
            $this->blockActions = [];
            for($i = 0, $len = $this->getVarInt(); $i < $len; ++$i){
                $blockAction = new \stdClass;
                $blockAction->actionType = $this->getVarInt();
                switch($blockAction->actionType){
                    case PlayerActionPacket::ACTION_START_BREAK:
                    case PlayerActionPacket::ACTION_ABORT_BREAK:
                    case PlayerActionPacket::ACTION_CRACK_BREAK:
                    case PlayerActionPacket::ACTION_PREDICT_DESTROY_BLOCK:
                    case PlayerActionPacket::ACTION_CONTINUE_DESTROY_BLOCK:
                        $this->getSignedBlockPosition($blockAction->x, $blockAction->y, $blockAction->z);
                        $blockAction->face = $this->getVarInt();
                }

                $this->blockActions[] = $blockAction;
            }
        }
    }

    protected function encodePayload(): void{
        parent::encodePayload();

        if($this->hasInputFlag(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION)){
            $out = $this;
            $out->writeGenericTypeNetworkId($this->requestId);
            if($this->requestId !== 0){
                $out->putUnsignedVarInt(count($this->requestChangedSlots));
                foreach($this->requestChangedSlots as $changedSlots){
                    $changedSlots->write($out);
                }
            }

            $this->transactionData->encode($out);
        }

        if($this->getInputFlags(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST)){
            $this->itemStackRequest->write($this);
        }

        if($this->getInputFlags(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS)){
            $this->putVarInt(count($this->blockActions));
            foreach($this->blockActions as $blockAction){
                $this->putVarInt($blockAction->actionType);
                switch($blockAction->actionType){
                    case PlayerActionPacket::ACTION_START_BREAK:
                    case PlayerActionPacket::ACTION_ABORT_BREAK:
                    case PlayerActionPacket::ACTION_CRACK_BREAK:
                    case PlayerActionPacket::ACTION_PREDICT_DESTROY_BLOCK:
                    case PlayerActionPacket::ACTION_CONTINUE_DESTROY_BLOCK:
                        $this->putSignedBlockPosition($blockAction->x, $blockAction->y, $blockAction->z);
                        $this->putVarInt($blockAction->face);
                }
            }
        }
    }
}