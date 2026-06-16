<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616221000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update arrival directions with detailed Rajaaram route';
    }

    public function up(Schema $schema): void
    {
        $pt = <<<'TEXT'
Ao sair de Alto Paraíso de Goiás, siga em direção ao povoado do Moinho. Recomendamos Google Maps (não Waze) e baixar o mapa offline antes de iniciar a rota.

1. No caminho, você encontrará uma bifurcação — mantenha-se à esquerda, seguindo as placas indicativas para o Moinho.
2. Ao chegar à placa "Bem-vindos ao Moinho", atravesse a ponte e continue em direção ao Solarion e às cachoeiras Anjos e Arcanjos.
3. Logo após a ponte, pegue a primeira rua à esquerda.
4. Em seguida, a primeira rua à direita.
5. Siga até o final da rua e, no final, vire à esquerda na estrada de terra, continuando pelo caminho das cachoeiras.
6. Após cerca de 200 metros, você verá uma subida à direita — postes laranjas dos dois lados da entrada e uma placa do Rajaaram. Suba essa rua até o final.

Chegando: você saberá que chegou ao encontrar a placa "Ater Tumti" e ao ser recebido(a) pelos nossos cachorros. São extremamente dóceis e amigáveis — fique tranquilo(a) se estiverem soltos.

🚗 Estacionamento: fiquem à vontade para estacionar no local. Ao chegar, toque o sino e logo iremos recebê-los.

🚙 Acesso: recomendamos veículos mais altos, pois o último trecho possui irregularidades típicas de estrada de terra. Se estiver com carro baixo ou não se sentir confortável, oferecemos translado gratuito entre o povoado do Moinho e o Rajaaram — avise com antecedência.

🚐 Transfer opcional: também oferecemos serviço de transfer saindo de Alto Paraíso, mediante valor adicional, para que você relaxe desde o início da jornada.

Qualquer dúvida durante o trajeto, entre em contato conosco — teremos prazer em ajudar.
TEXT;

        $en = <<<'TEXT'
When leaving Alto Paraíso de Goiás, head toward the village of Moinho. We recommend Google Maps (not Waze) and downloading offline maps before you start.

1. On the way, you will reach a fork — stay left, following signs to Moinho.
2. When you reach the "Bem-vindos ao Moinho" sign, cross the bridge and continue toward Solarion and the Anjos and Arcanjos waterfalls.
3. Right after the bridge, take the first street on the left.
4. Then the first street on the right.
5. Follow to the end of the street and, at the end, turn left onto the dirt road continuing along the waterfall trail.
6. After about 200 meters, you will see an uphill turn on the right — orange posts on both sides of the entrance and a Rajaaram sign. Drive up that road to the end.

Arrival: you will know you have arrived when you see the "Ater Tumti" sign and are greeted by our dogs. They are extremely gentle and friendly — don't worry if they are roaming freely.

🚗 Parking: feel free to park on site. When you arrive, ring the bell and we will come to welcome you.

🚙 Road access: we recommend higher vehicles, as the last stretch has typical dirt-road bumps. If you have a low car or don't feel comfortable, we offer a free shuttle between Moinho village and Rajaaram — just let us know in advance.

🚐 Optional transfer: we also offer a transfer from Alto Paraíso for an additional fee, so you can relax from the very start of your journey.

If you have any questions along the way, contact us — we will be happy to help.
TEXT;

        $this->addSql('UPDATE property SET arrival_instructions_pt = ?, arrival_instructions_en = ?', [$pt, $en]);
    }

    public function down(Schema $schema): void
    {
        $pt = <<<'TEXT'
O Domo Xangô está no Moinho, cerca de 14 km do centro de Alto Paraíso, por estrada de terra bem conservada. Recomendamos Google Maps (não Waze) e baixar o mapa offline.

1. Ao sair de Alto Paraíso, abra o GPS no Google Maps antes de iniciar a rota.
2. Siga pela estrada de terra em direção ao Moinho.
3. Na bifurcação, mantenha-se à esquerda, seguindo as placas indicativas.
4. Passe pela placa "Bem-vindos ao Moinho" e atravesse uma ponte.
5. Continue em direção ao Solarion e às Cachoeiras Anjos e Arcanjos: vire na primeira rua à esquerda, depois na primeira à direita.
6. Ao final da rua, vire à esquerda na estrada de terra (rumo às cachoeiras).
7. Após ~200 m, verá dois postes laranjas marcando a subida final à direita, engate a primeira marcha e suba devagar.

Chegando: à esquerda, entrada com placas "ATER TUMTI". Toque o sino no portal e aguarde o anfitrião.
TEXT;

        $en = <<<'TEXT'
Domo Xangô is in Moinho, about 14 km from Alto Paraíso center via a well-maintained dirt road. Use Google Maps (not Waze) and download offline maps.

1. Open Google Maps before leaving Alto Paraíso.
2. Follow the dirt road toward Moinho.
3. At the fork, stay left following signs.
4. Pass the "Bem-vindos ao Moinho" sign and cross a bridge.
5. Head toward Solarion and Anjos/Arcanjos waterfalls: first left, then first right.
6. At the end of the street, turn left on the dirt road toward the waterfalls.
7. After ~200 m, two orange posts mark the final uphill turn on the right, use first gear and go slowly.

Arrival: on your left, entrance with "ATER TUMTI" signs. Ring the bell at the gate and wait for your host.
TEXT;

        $this->addSql('UPDATE property SET arrival_instructions_pt = ?, arrival_instructions_en = ?', [$pt, $en]);
    }
}
